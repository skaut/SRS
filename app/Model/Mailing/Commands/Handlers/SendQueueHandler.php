<?php

declare(strict_types=1);

namespace App\Model\Program\Commands\Handlers;

use App\Model\Program\Commands\RemoveBlock;
use App\Model\Program\Commands\RemoveProgram;
use App\Model\Program\Commands\SendQueue;
use App\Model\Program\Repositories\BlockRepository;
use App\Services\CommandBus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class SendQueueHandler implements MessageHandlerInterface
{
    private CommandBus $commandBus;

    private EntityManagerInterface $em;

    private BlockRepository $blockRepository;

    public function __construct(CommandBus $commandBus, EntityManagerInterface $em, BlockRepository $blockRepository)
    {
        $this->commandBus      = $commandBus;
        $this->em              = $em;
        $this->blockRepository = $blockRepository;
    }

    public function __invoke(SendQueue $command): void
    {
        $this->em->transactional(function () use ($command): void {
            $block = $command->getBlock();

            foreach ($block->getPrograms() as $program) {
                $this->commandBus->handle(new RemoveProgram($program));
            }

            $this->blockRepository->remove($block);
        });
    }
}
