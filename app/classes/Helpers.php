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
    public static function renderBoolean($bool) {
        if ($bool) return 'ANO';
        return 'NE';
    }

}