<?php

declare(strict_types=1);

namespace App\Model\Cms\Dto;

/**
 * DTO obsahu s dokumenty.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class DocumentContentDto extends ContentDto
{
    /**
     * Tagy dokumentů, které se zobrazí.
     *
     * @var int[]
     */
    protected array $tags;

    /**
     * @param int[] $tags
     */
    public function __construct(string $type, string $heading, array $tags)
    {
        parent::__construct($type, $heading);
        $this->tags = $tags;
    }

    /**
     * @return int[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }
}
