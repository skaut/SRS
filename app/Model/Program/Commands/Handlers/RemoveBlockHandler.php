<?php

declare(strict_types=1);

namespace App\Model\Program\Commands\Handlers;

use App\Model\Program\Commands\RemoveBlock;
use App\Model\Program\Commands\RemoveProgram;
use App\Model\Program\Repositories\BlockRepository;
use App\Services\CommandBus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class RemoveBlockHandler implements MessageHandlerInterface
{
    public function __construct(
        private CommandBus $commandBus,
        private EntityManagerInterface $em,
        private BlockRepository $blockRepository,
    ) {
    }

    public function __invoke(RemoveBlock $command): void
    {
        $this->em->wrapInTransaction(function () use ($command): void {
            $block = $command->getBlock();

            foreach ($block->getPrograms() as $program) {
                $this->commandBus->handle(new RemoveProgram($program));
            }

            $this->blockRepository->remove($block);
        });
    }
}
