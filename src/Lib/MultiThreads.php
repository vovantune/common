<?php
declare(strict_types=1);
declare(ticks=1);

namespace ArtSkills\Lib;

use ArtSkills\Traits\Singleton;
use Cake\Cache\Cache;
use Cake\Log\Log;

/**
 * Класс для параллельного запуска функции в несколько потоков. Удобно применять для ускорения парсинга (параллелизация запросов),
 * а также ускорения математических рассчётов (для того же ClickHouse).
 * Следует помнить, что от запускаемой в отдельном потоке функции передать какие-то данные в родитель обычном способом нельзя,
 * т.е. выполняемая в отдельном потоке функция должна быть самодостаточной.
 * <br />
 * Для определения кол-ва потоков необходимо в app.php прописать константу:
 * `'threadsLimit' => 6, // int - сколько потоков запускать одновременно` <br />
 * Как использовать:
 * ```php
 * foreach ($steps as $step) {
 *    MultiThreads::getInstance()->run(function () use ($step) {
 *       // функция отдельного потока
 *       ...
 *    });
 * }
 * MultiThreads::getInstance()->waitThreads(); // обязательно необходимо вызвать в самом конце, чтобы дождаться завершения работы всех потомков
 * ```
 * Для того, чтобы на старте не дать одновременно большое кол-во запросов на API/ClickHouse, можно применить плавный запуск -
 * первые threadsLimit запросов будут запущены с таймаутом:
 * ```php
 * MultiThreads::getInstance()->run(function () use ($step) {
 *    // функция отдельного потока
 *    ...
 * }, 10); // первые threadsLimit процессов будут запускаться через 10 секунд
 * ```
 */
class MultiThreads
{
    use Singleton;

    /**
     * @var array<int, bool>
     */
    private array $_currentJobs = [];

    /**
     * @var array<int, int>
     */
    private array $_signalQueue = [];

    /**
     * @var bool
     */
    private bool $_isChild = false;

    /**
     * Счётчик запущенных процессов
     *
     * @var int
     */
    private int $_childCounter = 0;

    /**
     * MultiThreads constructor.
     */
    private function __construct()
    {
        pcntl_signal(SIGCHLD, [$this, "childSignalHandler"]);
    }

    /**
     * Определяем максимально возможное кол-во процессов.
     * Специально не кешируется для возможности правки на лету
     *
     * @return int
     */
    public function getProcessLimit(): int
    {
        $maxProcesses = (int)Env::getThreadsLimit();
        if (!$maxProcesses) {
            Log::error('Bad config for "threadsLimit"');
            $maxProcesses = 6;
        }
        return $maxProcesses;
    }

    /**
     * Проверка на вызов ожидания окончания процессов
     */
    public function __destruct()
    {
        if (!$this->_isChild) {
            if ($this->getTotalThreads()) {
                Log::error("MultiThread::waitThreads is not called!");
            }
            pcntl_signal(SIGCHLD, SIG_DFL);
        }
    }

    /**
     * Запуск в соседнем потоке метода
     *
     * @param callable $runFunction
     * @param int $softRunSleep Через сколько секунд запускать следующий поток для первых getProcessLimit() потоков - необходимо для уменьшиния нагрузки на БД
     */
    public function run(callable $runFunction, int $softRunSleep = 0)
    {
        while ($this->getTotalThreads() >= $this->getProcessLimit()) {
            sleep(1);
        }

        $this->_childCounter++;
        $this->_launchJob($runFunction, $softRunSleep);
    }

    /**
     * Ждём окончания выполнения тредов
     */
    public function waitThreads()
    {
        if ($this->_isChild) {
            return;
        }

        while ($this->getTotalThreads()) {
            sleep(1);
        }

        $connection = DB::getConnection();
        if (!$connection->isConnected()) {
            $connection->connect();
        }
    }

    /**
     * Вычисляем кол-во запущенных потоков
     *
     * @return int
     */
    public function getTotalThreads(): int
    {
        if ($this->_isChild) {
            return 0;
        } else {
            return count($this->_currentJobs);
        }
    }

    /**
     * Обработчик сигналов от потомков
     *
     * @internal
     */
    public function childSignalHandler()
    {
        if ($this->_isChild) {
            return;
        }

        $pid = pcntl_waitpid(-1, $status, WNOHANG);

        //Make sure we get all of the exited children
        while ($pid > 0) {
            if ($pid) {
                if (isset($this->_currentJobs[$pid])) {
                    $this->_processChildExit($pid, $status);
                } else {
                    //Oh no, our job has finished before this parent process could even note that it had been launched!
                    //Let's make note of it and handle it when the parent process is ready for it
                    $this->_signalQueue[$pid] = $status;
                }
            }
            $pid = pcntl_waitpid(-1, $status, WNOHANG);
        }
    }

    /**
     * Обрабатываем кончину потомка
     *
     * @param int $pid
     * @param int $status
     */
    private function _processChildExit(int $pid, int $status)
    {
        if ($this->_isChild) {
            return;
        }

        if (array_key_exists($pid, $this->_currentJobs)) {
            unset($this->_currentJobs[$pid]);
        }
        if (array_key_exists($pid, $this->_signalQueue)) {
            unset($this->_signalQueue[$pid]);
        }

        $exitCode = pcntl_wexitstatus($status);
        if ($exitCode != 0) {
            Log::error("$pid exited with status " . $exitCode);
        }
    }

    /**
     * Запуск метода в новом процессе
     *
     * @param callable $runFunction
     * @param int $softRunSleep
     * @SuppressWarnings(PHPMD.ExitExpression)
     */
    private function _launchJob(callable $runFunction, int $softRunSleep)
    {
        if (DB::getConnection()->isConnected()) {
            DB::getConnection()->disconnect();
        }

        $pid = pcntl_fork();
        if ($pid == -1) {
            //Problem launching the job
            Log::error('Could not launch new job, exiting');
        } elseif ($pid) {
            // Parent process
            // Sometimes you can receive a signal to the childSignalHandler function before this code executes if
            // the child script executes quickly enough!
            //
            $this->_currentJobs[$pid] = true;

            // In the event that a signal for this pid was caught before we get here, it will be in our signalQueue array
            // So let's go ahead and process it now as if we'd just received the signal
            if (isset($this->_signalQueue[$pid])) {
                $this->_processChildExit($pid, $this->_signalQueue[$pid]);
            }
        } else {
            //Forked child, do your deeds....
            $this->_isChild = true;
            DB::getConnection()->connect();
            Cache::disable();

            // плавный запуск множества потоков, чтобы не грузить БД/API
            if ($softRunSleep > 0 && $this->_childCounter <= $this->getProcessLimit()) {
                sleep(($this->_childCounter - 1) * $softRunSleep);
            }

            $runFunction();
            if (DB::getConnection()->isConnected()) {
                DB::getConnection()->disconnect();
            }
            exit(0);
        }
    }
}
