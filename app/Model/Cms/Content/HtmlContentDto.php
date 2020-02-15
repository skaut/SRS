<?php

declare(strict_types=1);

namespace App\Model\Cms\Content;

/**
 * DTO obsahu s HTML.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class HtmlContentDto extends ContentDto
{
    /**
     * Text.
     *
     * @var string
     */
    protected $text;

    public function __construct(string $type, string $heading, ?string $text)
    {
        parent::__construct($type, $heading);
        $this->text = $text;
    }

    public function getText() : ?string
    {
        return $this->text;
    }
}
