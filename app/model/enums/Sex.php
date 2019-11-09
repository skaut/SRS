<?php

declare(strict_types=1);

namespace App\Model\Enums;

class Sex
{
    /**
     * Muž.
     *
     * @var string
     */
    public const MALE = 'male';

    /**
     * Žena.
     *
     * @var string
     */
    public const FEMALE = 'female';

    /** @var string[] */
    public static $sex = [
        self::MALE,
        self::FEMALE,
    ];

    /**
     * Vrací možnosti pohlaví pro select.
     *
     * @return string[]
     */
    public static function getSexOptions() : array
    {
        $options = [];
        foreach (self::$sex as $s) {
            $options[$s] = 'common.sex.' . $s;
        }
        return $options;
    }
}
