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
     * 105.5 up to nearest 1 = 106
     * 105.5 up to nearest 10 = 110
     * 105.5 up to nearest 7 = 112
     * 105.5 up to nearest 100 = 200
     * 105.5 up to nearest 0.2 = 105.6
     * 105.5 up to nearest 0.3 = 105.6
     *
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
