<?php

declare(strict_types=1);

namespace App\Model\Program\Commands;

use App\Model\Program\Room;

class RemoveRoom
{
    public function __construct(private Room $room)
    {
    }

    public function getRoom(): Room
    {
        return $this->room;
    }
}
