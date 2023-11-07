<?php

declare(strict_types=1);

namespace App\Model\Program\Commands;

use App\Model\Program\Room;

class SaveRoom
{
    public function __construct(private readonly Room $room)
    {
    }

    public function getRoom(): Room
    {
        return $this->room;
    }
}
