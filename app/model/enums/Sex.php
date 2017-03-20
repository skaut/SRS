<?php

namespace App\Model\Enums;


class Sex
{
    /**
     * Muž.
     */
    const MALE = 'male';

    /**
     * Žena.
     */
    const FEMALE = 'female';

    public static $sex = [
        self::MALE,
        self::FEMALE
    ];


    /**
     * Vrací možnosti pohlaví pro select.
     * @return array
     */
    public static function getSexOptions()
    {
        $options = [];
        foreach (self::$sex as $s) {
            $options[$s] = 'common.sex.' . $s;
        }
        return $options;
    }
}