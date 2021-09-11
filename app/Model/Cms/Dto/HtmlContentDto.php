<?php

declare(strict_types=1);

namespace App\Model\Cms\Dto;

/**
 * DTO obsahu s HTML.
 */
class HtmlContentDto extends ContentDto
{
    /**
     * Text.
     */
    protected ?string $text = null;

    public function __construct(string $type, string $heading, ?string $text)
    {
        parent::__construct($type, $heading);
        $this->text = $text;
    }

    public function getText(): ?string
    {
        return $this->text;
    }
}
