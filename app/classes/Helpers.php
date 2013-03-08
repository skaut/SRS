<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 19.2.13
 * Time: 18:08
 * To change this template use File | Settings | File Templates.
 */

namespace SRS;

class Helpers
{
     const DATE_PATTERN = '([0-9]){4}-([0-9]){2}-([0-9]){2}';


    public static function renderBoolean($bool) {
        if ($bool) return 'ANO';
        return 'NE';
    }


}