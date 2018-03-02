<?php
declare(strict_types=1);

namespace App\Utils;


/**
 * Třída s pomocnými metodami.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class Helpers
{
    public const DATE_FORMAT = 'j. n. Y';

    public const DATETIME_FORMAT = 'j. n. Y H:i';

    /**
     * Zkrátí $text na $length znaků a doplní '...'.
     * @param string $text
     * @param int $length
     * @return string
     */
    public static function truncate(string $text, int $length): string
    {
        if (strlen($text) > $length) {
            $text = $text . " ";
            $text = mb_substr($text, 0, $length, 'UTF-8');
            $text = mb_substr($text, 0, strrpos($text, ' '), 'UTF-8');
            $text = $text . "...";
        }
        return $text;
    }
}
