<?php

declare(strict_types=1);

namespace App\Model\CMS\Content;

/**
 * DTO obsahu s aktualitami.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class NewsContentDTO extends ContentDTO
{
    /**
     * Počet posledních novinek k zobrazení.
     * @var int
     */
    protected $count;


    /**
     * NewsContent constructor.
     * @param string $type
     * @param string $heading
     * @param int $count
     */
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
