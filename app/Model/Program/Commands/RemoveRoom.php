<?php

declare(strict_types=1);

namespace App\Model\Program\Commands;

use App\Model\Program\Room;

class RemoveRoom
{
    private Room $room;

    public function __construct(Room $room)
    {
        $this->room = $room;
    }

    public function getRoom(): Room
    {
        return $this->room;
    }
}
