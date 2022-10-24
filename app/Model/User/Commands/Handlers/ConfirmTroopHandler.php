<?php

declare(strict_types=1);

namespace App\Model\User\Commands\Handlers;

use App\Model\User\Commands\RegisterProgram;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class ConfirmTroopHandler implements MessageHandlerInterface
{
    public function __construct()
    {
    }

    public function __invoke(RegisterProgram $command): void
    {
    }
}
