<?php

declare(strict_types=1);

namespace App\Model\Enums;

class SkautIsEventType
{
    /**
     * Vzdělávací akce.
     */
    public const EDUCATION = 'education';

    /**
     * Další akce.
     */
    public const GENERAL = 'general';

    /** @var string[] */
    public static $types = [
        self::GENERAL,
        self::EDUCATION,
    ];

    /**
     * Vrací možnosti typů akcí pro select.
     *
     * @return string[]
     */
    public static function getSkautIsEventTypesOptions() : array
    {
        $options = [];
        foreach (self::$types as $type) {
            $options[$type] = 'common.skautis_event_type.' . $type;
        }

        return $options;
    }
}
