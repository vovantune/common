<?php
declare(strict_types=1);

namespace ArtSkills\Database\Type;


use Cake\Database\Driver;
use Cake\Database\Type\StringType;

/**
 * Надстройка над строковым типом данных, который выпиливает все эмодзи.
 * Важно! В объекте результата $table->save() удаление символов не происходит, а только в таблице.
 * Подключение:
 *  В bootstrap.php прописываем следуюее:
 * ```
 * \Cake\Database\Type::map('text', \ArtSkills\Database\Type\Utf8StringType::class);
 * \Cake\Database\Type::map('string', \ArtSkills\Database\Type\Utf8StringType::class);
 * ```
 */
class Utf8StringType extends StringType
{
	/** @inheritdoc */
	public function toDatabase($value, Driver $driver)
	{
		if ($value !== null && is_string($value)) {
			$value = $this->_replace4byte($value);
		}

		return parent::toDatabase($value, $driver);
	}

	/**
	 * Вырезаем все 16 байтные эмодзи
	 *
	 * @param string $string
	 * @return string
	 */
	private function _replace4byte(string $string): string
	{
		return preg_replace('%(?:
          \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
        | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
        | \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
    )%xs', '', $string);
	}
}