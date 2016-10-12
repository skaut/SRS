<?php
/**
 * Date: 19.2.13
 * Time: 18:08
 * Author: Michal Májský
 */

namespace SRS;

/**
 * Pomocne konstanty a funkce
 */
class Helpers
{
    const DATE_PATTERN = '([0-9]){2}.([0-9]){2}.([0-9]){4}';
    const DATETIME_PATTERN = '(([0-9]){2}.([0-9]){2}.([0-9]){4} ([0-9]){2}:([0-9]){2})|(([0-9]){2}.([0-9]){2}.([0-9]){4})';


    public static function renderBoolean($bool)
    {
        if ($bool) return 'ANO';
        return 'NE';
    }


}