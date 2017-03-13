<?php
namespace ArtSkills\Lib;

use ArtSkills\Traits\Library;

/**
 * Русские нештяки
 */
class RusGram
{
	use Library;

	/**
	 * Скланение в зависимости от значения $digit
	 *
	 * @param int $digit
	 * @param array $expressionList массив из 3 фраз - [именительный, винительный, родительный]
	 * @param bool $returnOnlyWord - не возвращать в строке число $digit
	 * @return string
	 *
	 */
	public static function declension($digit, $expressionList, $returnOnlyWord = false) {
		if (empty($expressionList[2])) {
			$expressionList[2] = $expressionList[1];
		}
		$rest = preg_replace('/\D+/s', '', $digit) % 100;
		if ($returnOnlyWord) {
			$digit = '';
		}
		if ($rest >= 5 && $rest <= 20) {
			$result = $digit . ' ' . $expressionList[2];
		} else {
			$rest %= 10;
			if ($rest == 1) {
				$result = $digit . ' ' . $expressionList[0];
			} elseif ($rest >= 2 && $rest <= 4) {
				$result = $digit . ' ' . $expressionList[1];
			} else {
				$result = $digit . ' ' . $expressionList[2];
			}
		}
		return trim($result);
	}

	/**
	 * Формируем строку даты в русском формате
	 *
	 * @param string $format FI - месяц в именительном педеже, FR - месяц в родительном падеже, M - краткое название
	 *     месяца, l - день недели, D - краткий день недели с указанием "сегодня" и "завтра"
	 * @param string|int $date дата в формате unixtime или Y-m-d строка
	 * @return string
	 *
	 */
	public static function getRussianDate($format, $date) {
		$monthList = [
			'January' => 'январь',
			'February' => 'февраль',
			'March' => 'март',
			'April' => 'апрель',
			'May' => 'май',
			'June' => 'июнь',
			'July' => 'июль',
			'August' => 'август',
			'September' => 'сентябрь',
			'October' => 'октябрь',
			'November' => 'ноябрь',
			'December' => 'декабрь',
		];

		$monthGenetiveList = [
			'January' => 'января',
			'February' => 'февраля',
			'March' => 'марта',
			'April' => 'апреля',
			'May' => 'мая',
			'June' => 'июня',
			'July' => 'июля',
			'August' => 'августа',
			'September' => 'сентября',
			'October' => 'октября',
			'November' => 'ноября',
			'December' => 'декабря',
		];

		$shortMonthList = [
			'Jan' => 'Янв',
			'Feb' => 'Фев',
			'Mar' => 'Мар',
			'Apr' => 'Апр',
			'May' => 'Май',
			'Jun' => 'Июн',
			'Jul' => 'Июл',
			'Aug' => 'Авг',
			'Sep' => 'Сен',
			'Oct' => 'Окт',
			'Nov' => 'Ноя',
			'Dec' => 'Дек',
		];

		$fullDayList = [
			'Monday' => 'Понедельник',
			'Tuesday' => 'Вторник',
			'Wednesday' => 'Среда',
			'Thursday' => 'Четверг',
			'Friday' => 'Пятница',
			'Saturday' => 'Суббота',
			'Sunday' => 'Воскресенье',
		];

		$shortDayList = [
			'Mon' => 'Пн',
			'Tue' => 'Вт',
			'Wed' => 'Ср',
			'Thu' => 'Чт',
			'Fri' => 'Пт',
			'Sat' => 'Сб',
			'Sun' => 'Вс',
		];

		$result = date(str_replace(['FI', 'FR'], ['F', 'F'], $format), is_int($date) ? $date : strtotime($date));

		if (strstr($format, 'FI')) {
			$result = str_replace(array_keys($monthList), $monthList, $result);
		}

		if (strstr($format, 'FR')) {
			$result = str_replace(array_keys($monthGenetiveList), $monthGenetiveList, $result);
		}

		if (strstr($format, 'M')) {
			$result = str_replace(array_keys($shortMonthList), $shortMonthList, $result);
		}

		if (strstr($format, 'l')) {
			$result = str_replace(array_keys($fullDayList), $fullDayList, $result);
		}

		if (strstr($format, 'D')) {
			$result = str_replace(array_keys($shortDayList), $shortDayList, $result);
		}

		return $result;
	}
}
