<?php

namespace App\Model\Enums;


class Sex
{
    const MALE = 'male';
    const FEMALE = 'female';

    public static $sex = [
        self::MALE,
        self::FEMALE
    ];

    public static function getSexOptions()
    {
        $options = [];
        foreach (self::$sex as $s) {
            $options[$s] = 'common.sex.' . $s;
        }
        return $options;
    }
}