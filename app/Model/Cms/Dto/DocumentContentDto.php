<?php

declare(strict_types=1);

namespace App\Model\Cms\Dto;

/**
 * DTO obsahu s dokumenty.
 */
class DocumentContentDto extends ContentDto
{
    /**
     * Tagy dokumentů, které se zobrazí
     *
     * @param int[] $tags
     */
    public function __construct(string $type, string $heading, protected array $tags)
    {
        parent::__construct($type, $heading);
    }

    /**
     * @return int[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }
}
