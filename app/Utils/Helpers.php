<?php

declare(strict_types=1);

namespace App\Utils;

use Doctrine\Common\Collections\Collection;

use function array_diff;
use function array_map;
use function mb_strlen;
use function mb_strrpos;
use function mb_substr;

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
     * Zkrátí $text na maximálně $length znaků včetně '…'.
     */
    public static function truncate(string $text, int $length): string
    {
        if (mb_strlen($text, 'UTF-8') > $length) {
            $text = mb_substr($text, 0, $length, 'UTF-8');
            if (mb_strrpos($text, ' ', 0, 'UTF-8') !== false) {
                $text = mb_substr($text, 0, mb_strrpos($text, ' ', 0, 'UTF-8'), 'UTF-8');
            } else {
                $text = mb_substr($text, 0, $length - 1, 'UTF-8');
            }

            $text .= '…';
        }

        return $text;
    }

    /**
     * Vrátí id prvků v kolekci.
     *
     * @param Collection<int, object> $collection
     *
     * @return int[]
     */
    public static function getIds(Collection $collection): array
    {
        return array_map(static function ($o) {
            return $o->getId();
        }, $collection->toArray());
    }

    /**
     * Porovnává kolekce entit podle id.
     *
     * @param Collection<int, object> $collection1
     * @param Collection<int, object> $collection2
     */
    public static function collectionsEquals(Collection $collection1, Collection $collection2): bool
    {
        if ($collection1->count() !== $collection2->count()) {
            return false;
        }

        $collection1Ids = self::getIds($collection1);
        $collection2Ids = self::getIds($collection2);

        return array_diff($collection1Ids, $collection2Ids) === array_diff($collection2Ids, $collection1Ids);
    }
}
