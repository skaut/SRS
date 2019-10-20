<?php

declare(strict_types=1);

namespace App\Model\CMS\Content;

/**
 * DTO obsahu s textem.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class TextContentDTO extends ContentDTO
{
    /**
     * Text.
     * @var string
     */
    protected $text;


    /**
     * TextContent constructor.
     * @param string $type
     * @param string $heading
     * @param string $text
     */
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
