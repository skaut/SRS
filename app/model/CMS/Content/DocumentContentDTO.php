<?php

declare(strict_types=1);

namespace App\Model\CMS\Content;

/**
 * DTO obsahu s dokumenty.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class DocumentContentDTO extends ContentDTO
{
    /**
     * Tagy dokumentů, které se zobrazí.
     * @var int[]
     */
    protected $tags;


    /**
     * DocumentContent constructor.
     * @param string $type
     * @param string $heading
     * @param array $tags
     */
    public function __construct(string $type, string $heading, array $tags)
    {
        parent::__construct($type, $heading);
        $this->tags = $tags;
    }

    /**
     * @return int[]
     */
    public function getTags() : array
    {
        return $this->tags;
    }
}
