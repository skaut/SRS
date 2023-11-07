<?php

declare(strict_types=1);

namespace App\Model\Cms\Dto;

/**
 * DTO obsahu s aktualitami.
 */
class NewsContentDto extends ContentDto
{
    /** @param ?int $count Počet posledních novinek k zobrazení */
    public function __construct(string $type, string $heading, protected int|null $count)
    {
        parent::__construct($type, $heading);
    }

    public function getCount(): int|null
    {
        return $this->count;
    }
}
