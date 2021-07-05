<?php
declare(strict_types=1);

namespace ArtSkills\Controller;

use ArtSkills\Filesystem\File;
use ArtSkills\Http\Client;
use ArtSkills\Lib\Arrays;
use ArtSkills\Lib\Env;
use ArtSkills\Lib\Url;
use Cake\Cache\Cache;
use Cake\Http\Response;
use OpenApi\Annotations\Contact;
use OpenApi\Annotations\Info;
use ZipArchive;
use function OpenApi\scan;

/**
 * Формируем документацию по API
 *
 * Конфигурация описывается в файле app.php:
 * ```php
 * 'apiInfo' => [
 *    'title' => 'Eggheads.Solutions Api',
 *    'description' => 'Eggheads.Solutions Api. Документ сформирован автоматически, онлайн <a href="https://github.com/swagger-api/swagger-codegen/tree/3.0.0#online-generators">генератор кода API</a>',
 *    'version' => '1',
 *    'contact' => [
 *       'email' => 'tune@eggheads.solutions',
 *       'url' => '/apiDocumentation.json', // путь к контроллеру
 *    ],
 * ],
 * ```
 */
class ApiDocumentationController extends Controller
{
    protected const DOCUMENTATION_CACHE_PROFILE = 'default';

    /**
     *
     * Формируем документацию как в HTML, так и в JSON формате
     * @OA\Get(
     *  path = "apiDocumentation.json",
     *  tags = {"Documentation"},
     *  summary = "Документация по API",
     *  operationId = "apiDocumentation",
     *  @OA\Response(
     *    response = 200,
     *    description = "Результат запроса",
     *    @OA\JsonContent(ref = "#/components/schemas/ApiResponse")
     *  )
     * )
     *
     * @return Response|null
     */
    public function index(): ?Response
    {
        if ($this->_responseExtension !== self::REQUEST_EXTENSION_DEFAULT) {
            return $this->_sendTextResponse(json_encode($this->_getJson(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 'json');
        } else {
            return $this->_sendTextResponse($this->_getHtml(), 'html');
        }
    }

    /**
     * Формируем выдачу в формате JSON
     * @OA\Info(
     *  title = "",
     *  description =  "",
     *  @OA\Contact(
     *      email = ""
     *  ),
     *  version = "1.0"
     * )
     *
     * @return array
     * @SuppressWarnings(PHPMD.MethodArgs)
     * @phpstan-ignore-next-line
     */
    private function _getJson(): array
    {
        return Cache::remember('ApiDocumentationJson#' . CORE_VERSION, function () {
            $apiInfo = Env::getApiInfo();
            if (empty($apiInfo)) {
                $apiInfo = [];
            }
            $apiInfo += [
                'title' => 'Eggheads.Solutions Api',
                'description' => 'Eggheads.Solutions Api. Документ сформирован автоматически, онлайн <a href="https://github.com/swagger-api/swagger-codegen/tree/3.0.0#online-generators">генератор кода API</a>',
                'version' => '1',
                'contact' => [
                    'email' => 'tune@eggheads.solutions',
                    'url' => '/apiDocumentation.json',
                ],
            ];

            $swagger = scan([APP, __DIR__]);
            $swagger->info = new Info([
                'title' => $apiInfo['title'],
                'description' => $apiInfo['description'],
                'version' => $apiInfo['version'],
                'contact' => new Contact([
                    'email' => $apiInfo['contact']['email'],
                    'url' => Url::withDomainAndProtocol($apiInfo['contact']['url']),
                ]),
            ]);

            return json_decode(json_encode($swagger), true);
        }, static::DOCUMENTATION_CACHE_PROFILE);
    }

    /**
     * Формируем выдачу в формате HTML
     *
     * @see https://github.com/swagger-api/swagger-codegen/tree/3.0.0#online-generators
     *
     * @return string
     */
    private function _getHtml(): string
    {
        return Cache::remember('ApiDocumentationHtml#' . CORE_VERSION, function () {
            $client = new Client();
            $result = $client->post('https://generator3.swagger.io/api/generate', Arrays::encode([
                'specURL' => Url::withDomainAndProtocol('apiDocumentation.json'),
                'lang' => 'html2',
                'type' => 'CLIENT',
                'codegenVersion' => 'V3',
            ]), [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]);
            $this->_throwUserError('Ошибка запроса к внешнему сервису', empty($result));

            $file = new File(File::generateTempFilePath('api-documentation'));
            $file->write($result->getStringBody());
            $file->close();

            $archive = new ZipArchive();
            $archive->open($file->path);
            $htmlString = $archive->getFromName('index.html');
            if (empty($htmlString)) {
                $this->_throwInternalError("Пустой файл с API документацией!");
            }
            $file->delete();
            return $htmlString;
        }, static::DOCUMENTATION_CACHE_PROFILE);
    }
}
