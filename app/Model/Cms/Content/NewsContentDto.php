<?php

declare(strict_types=1);

namespace App\Model\Cms\Content;

/**
 * DTO obsahu s aktualitami.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class NewsContentDto extends ContentDto
{
    /**
     * Počet posledních novinek k zobrazení.
     */
    protected ?int $count;

    public function __construct(string $type, string $heading, ?int $count)
    {
        parent::__construct($type, $heading);
        $this->count = $count;
    }

    public function getCount() : ?int
    {
        return $this->count;
    }
}
