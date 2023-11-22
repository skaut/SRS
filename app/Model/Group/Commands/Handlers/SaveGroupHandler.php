<?php

declare(strict_types=1);

namespace App\Model\Group\Commands\Handlers;

use App\Model\Group\Commands\SaveGroup;
use App\Model\Group\Repositories\GroupRepository;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class SaveGroupHandler implements MessageHandlerInterface
{
    public function __construct(private GroupRepository $groupRepository)
    {
    }

    public function __invoke(SaveGroup $command): void
    {
        $this->groupRepository->save($command->getGroup());
    }
}
