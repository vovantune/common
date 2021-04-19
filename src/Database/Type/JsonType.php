<?php

namespace ArtSkills\Database\Type;

use Cake\Database\Driver;

/**
 * Поддержка типа данных JSON в MySQL, в отличие от CakePHP позволяет сохранять null значение.
 * # Подключение:
 * По-умолчанию включено bootstrap.php, если нет, то прописываем следующее:
 * ```php
 * \Cake\Database\Type::map('json', \ArtSkills\Database\Type\JsonType::class);
 * ```
 * См. более подробно в мануале [CakePHP](https://book.cakephp.org/3.0/en/orm/saving-data.html#saving-complex-types)
 */
class JsonType extends \Cake\Database\Type\JsonType
{

    /** @inheritdoc */
    public function toPHP($value, Driver $driver)
    {
        if ($value === null) {
            return null;
        }
        return parent::toPHP($value, $driver);
    }

    /** @inheritdoc */
    public function toDatabase($value, Driver $driver)
    {
        if ($value === null) {
            return null;
        }
        return parent::toDatabase($value, $driver);
    }

    /** @inheritdoc */
    public function toStatement($value, Driver $driver)
    {
        if ($value === null) {
            return \PDO::PARAM_NULL;
        }
        return parent::toStatement($value, $driver);
    }
}
