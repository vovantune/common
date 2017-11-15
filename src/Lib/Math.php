<?php
declare(strict_types=1);

namespace ArtSkills\Lib;

use ArtSkills\Traits\Library;

/**
 * Математические операции
 */
class Math
{
	use Library;

	/**
	 * Округляем до ближайшего числа с шагом $roundTo
	 * @param float $number
	 * @param float $roundTo
	 * @return float
	 */
	public static function roundUpToNearest(float $number, float $roundTo): float
	{
		if ($roundTo == 0) {
			return $number;
		} else {
			return ceil($number / $roundTo) * $roundTo;
		}
	}
}