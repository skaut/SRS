<?php

declare(strict_types=1);

namespace App\Model\Program\Commands\Handlers;

use App\Model\Program\Commands\SaveBlock;
use App\Model\Program\Events\BlockUpdatedEvent;
use App\Model\Program\Repositories\BlockRepository;
use App\Services\EventBus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class SaveBlockHandler implements MessageHandlerInterface
{
    public function __construct(
        private EventBus $eventBus,
        private EntityManagerInterface $em,
        private BlockRepository $blockRepository,
    ) {
    }

    public function __invoke(SaveBlock $command): void
    {
        $block    = $command->getBlock();
        $blockOld = $command->getBlockOld();

        if ($block->getId() === null) {
            $this->blockRepository->save($block);
        } else {
            $this->em->wrapInTransaction(function () use ($block, $blockOld): void {
                $categoryOld          = $blockOld->getCategory();
                $subeventOld          = $blockOld->getSubevent();
                $mandatoryOld         = $blockOld->getMandatory();
                $capacityOld          = $blockOld->getCapacity();
                $alternatesAllowedOld = $blockOld->isAlternatesAllowed();

                $this->blockRepository->save($block);

                $this->eventBus->handle(new BlockUpdatedEvent($block, $categoryOld, $subeventOld, $mandatoryOld, $capacityOld, $alternatesAllowedOld));
            });
        }
    }
}
