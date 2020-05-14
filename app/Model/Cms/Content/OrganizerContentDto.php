<?php

declare(strict_types=1);

namespace App\Model\Cms\Content;

/**
 * DTO obsahu s informací o pořadateli.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class OrganizerContentDto extends ContentDto
{
    /**
     * Pořadatel.
     */
    protected ?string $organizer;

    public function __construct(string $type, string $heading, ?string $organizer)
    {
        parent::__construct($type, $heading);
        $this->organizer = $organizer;
    }

    public function getOrganizer() : ?string
    {
        return $this->organizer;
    }
}
