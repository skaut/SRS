<?php
declare(strict_types=1);

namespace App\Model\Enums;


class SkautIsEventType
{
    /**
     * Vzdělávací akce.
     */
    const EDUCATION = 'education';

    /**
     * Další akce.
     */
    const GENERAL = 'general';

    public static $types = [
        self::GENERAL,
        self::EDUCATION
    ];


    /**
     * Vrací možnosti typů akcí pro select.
     * @return array
     */
    public static function getSkautIsEventTypesOptions(): array
    {
        $options = [];
        foreach (self::$types as $type) {
            $options[$type] = 'common.skautis_event_type.' . $type;
        }
        return $options;
    }
}
