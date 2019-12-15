<?php

declare(strict_types=1);

namespace App\Utils;

use Doctrine\Common\Collections\Collection;
use Nette\SmartObject;
use function array_map;
use function mb_substr;
use function strlen;
use function strrpos;

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
     */
    public static function truncate(string $text, int $length) : string
    {
        if (strlen($text) > $length) {
            $text = $text . ' ';
            $text = mb_substr($text, 0, $length, 'UTF-8');
            $text = mb_substr($text, 0, strrpos($text, ' '), 'UTF-8');
            $text = $text . '...';
        }
        return $text;
    }

    /**
     * Vrátí id prvků v kolekci.
     * @param Collection|object[] $collection
     * @return int[]
     */
    public static function getIds(Collection $collection) : array
    {
        return array_map(function ($o) {
            return $o->getId();
        }, $collection->toArray());
    }
}
