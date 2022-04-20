<?php

declare(strict_types=1);

namespace App\Model\Enums;

class CalendarView
{
    /**
     * Zobrazení na výšku
     */
    public const TIME_GRID = 'timeGridSeminar';

    /**
     * Zobrazení na šířku
     */
    public const RESOURCE_TIMELINE = 'resourceTimelineSeminar';

    /**
     * Zobrazení seznam
     */
    public const LIST = 'listSeminar';

    /** @var string[] */
    public static array $views = [
        self::TIME_GRID,
        self::RESOURCE_TIMELINE,
        self::LIST,
    ];

    /**
     * Vrací možnosti zobrazení pro select
     *
     * @return string[]
     */
    public static function getCalendarViewsOptions(): array
    {
        $options = [];
        foreach (self::$views as $view) {
            $options[$view] = 'common.calendar_view.' . $view;
        }

        return $options;
    }
}
