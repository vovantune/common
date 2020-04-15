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
	 */
	public static function declension($digit, $expressionList, $returnOnlyWord = false)
	{
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
	 */
	public static function getRussianDate($format, $date)
	{
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

	/**
	 * Переводим число в буквы
	 *
	 * @see https://gist.github.com/bupy7/26827cec44f4a8c01ff3
	 *
	 * @param int|float $num
	 * @return string
	 */
	public static function numberToString($num): string
	{
		$nul = 'ноль';
		$ten = [
			['', 'один', 'два', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять'],
			['', 'одна', 'две', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять'],
		];
		$a20 = ['десять', 'одиннадцать', 'двенадцать', 'тринадцать', 'четырнадцать', 'пятнадцать', 'шестнадцать', 'семнадцать', 'восемнадцать', 'девятнадцать'];
		$tens = [2 => 'двадцать', 'тридцать', 'сорок', 'пятьдесят', 'шестьдесят', 'семьдесят', 'восемьдесят', 'девяносто'];
		$hundred = ['', 'сто', 'двести', 'триста', 'четыреста', 'пятьсот', 'шестьсот', 'семьсот', 'восемьсот', 'девятьсот'];
		$unit = [ // Units
			['копейка', 'копейки', 'копеек', 1],
			['рубль', 'рубля', 'рублей', 0],
			['тысяча', 'тысячи', 'тысяч', 1],
			['миллион', 'миллиона', 'миллионов', 0],
			['миллиард', 'милиарда', 'миллиардов', 0],
		];
		//
		[$rub, $kop] = explode('.', sprintf("%015.2f", floatval($num)));
		$out = [];
		if (intval($rub) > 0) {
			foreach (str_split($rub, 3) as $uk => $v) { // by 3 symbols
				if (!intval($v)) continue;
				$uk = sizeof($unit) - $uk - 1; // unit key
				$gender = $unit[$uk][3];
				[$i1, $i2, $i3] = array_map('intval', str_split($v, 1));
				// mega-logic
				$out[] = $hundred[$i1]; # 1xx-9xx
				if ($i2 > 1) $out[] = $tens[$i2] . ' ' . $ten[$gender][$i3]; # 20-99
				else $out[] = $i2 > 0 ? $a20[$i3] : $ten[$gender][$i3]; # 10-19 | 1-9
				// units without rub & kop
				if ($uk > 1) $out[] = self::_morph($v, $unit[$uk][0], $unit[$uk][1], $unit[$uk][2]);
			} //foreach
		} else $out[] = $nul;
		$out[] = self::_morph(intval($rub), $unit[1][0], $unit[1][1], $unit[1][2]); // rub
		$out[] = $kop . ' ' . self::_morph($kop, $unit[0][0], $unit[0][1], $unit[0][2]); // kop
		return trim(preg_replace('/ {2,}/', ' ', join(' ', $out)));
	}

	/**
	 * Склоняем словоформу
	 *
	 * @param int $n
	 * @param string $f1
	 * @param string $f2
	 * @param string $f5
	 * @return string
	 */
	private static function _morph($n, $f1, $f2, $f5)
	{
		$n = abs(intval($n)) % 100;
		if ($n > 10 && $n < 20) return $f5;
		$n = $n % 10;
		if ($n > 1 && $n < 5) return $f2;
		if ($n == 1) return $f1;
		return $f5;
	}
}
