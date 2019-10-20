<?php

declare(strict_types=1);

namespace App\Model\CMS\Content;

/**
 * DTO obsahu s HTML.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class HtmlContentDTO extends ContentDTO
{
    /**
     * Text.
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
