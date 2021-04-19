<?php

namespace ArtSkills\Http;

use ArtSkills\Lib\Env;

class Client extends \Cake\Http\Client
{
    /**
     * Client constructor.
     *
     * @param array $config
     */
    public function __construct($config = ['redirect' => 2])
    {
        // возможность глобального переопределения адаптора отправки запросов
        if (Env::hasHttpClientAdapter()) {
            $config['adapter'] = Env::getHttpClientAdapter();
        }

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     * Обернул в try/catch для, дабы чтобы код не валилися
     */
    protected function _doRequest($method, $url, $data, $options)
    {
        if (!empty($data) && is_array($data)) { // костыль от попытки загрузить файл, если строка начинается с '@'
            $data = http_build_query($data);
        }

        return parent::_doRequest($method, $url, $data, $options);
    }
}
