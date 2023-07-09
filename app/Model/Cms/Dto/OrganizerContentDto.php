<?php

declare(strict_types=1);

namespace App\Model\Cms\Dto;

/**
 * DTO obsahu s informací o pořadateli.
 */
class OrganizerContentDto extends ContentDto
{
    /** @param ?string $organizer Pořadatel. */
    public function __construct(string $type, string $heading, protected string|null $organizer)
    {
        parent::__construct($type, $heading);
    }

    public function getOrganizer(): string|null
    {
        return $this->organizer;
    }
}
