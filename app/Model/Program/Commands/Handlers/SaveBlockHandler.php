<?php

declare(strict_types=1);

namespace App\Model\Program\Commands\Handlers;

use App\Model\Program\Commands\SaveBlock;
use App\Model\Program\Events\BlockUpdatedEvent;
use App\Model\Program\Repositories\BlockRepository;
use App\Services\EventBus;
use Nettrine\ORM\EntityManagerDecorator;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class SaveBlockHandler implements MessageHandlerInterface
{
    private EventBus $eventBus;

    private EntityManagerDecorator $em;

    private BlockRepository $blockRepository;

    public function __construct(EventBus $eventBus, EntityManagerDecorator $em, BlockRepository $blockRepository)
    {
        $this->eventBus        = $eventBus;
        $this->em              = $em;
        $this->blockRepository = $blockRepository;
    }

    public function __invoke(SaveBlock $command) : void
    {
        $block = $command->getBlock();

        if ($block->getId() === null) {
            $this->blockRepository->save($block);
        } else {
            $this->em->transactional(function () use ($block) : void {
                $originalBlock = $this->blockRepository->findById($block->getId());

                $originalCategory  = $originalBlock->getCategory();
                $originalSubevent  = $originalBlock->getSubevent();
                $originalMandatory = $originalBlock->getMandatory();

                $this->blockRepository->save($block);

                $this->eventBus->handle(new BlockUpdatedEvent($block, $originalCategory, $originalSubevent, $originalMandatory));
            });
        }
    }
}
