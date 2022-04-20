<?php

declare(strict_types=1);

namespace App\Model\Cms\Dto;

/**
 * DTO obsahu s textem
 */
class TextContentDto extends ContentDto
{
    public function __construct(string $type, string $heading, protected ?string $text)
    {
        parent::__construct($type, $heading);
    }

    public function getText(): ?string
    {
        return $this->text;
    }
}
