<?php

declare(strict_types=1);

namespace App\Model\Program\Commands\Handlers;

use App\Model\Program\Commands\RemoveBlock;
use App\Model\Program\Repositories\BlockRepository;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class RemoveBlockHandler implements MessageHandlerInterface
{
    private BlockRepository $blockRepository;

    public function __construct(BlockRepository $blockRepository)
    {
        $this->blockRepository = $blockRepository;
    }

    public function __invoke(RemoveBlock $command) : void
    {
        $this->blockRepository->remove($command->getBlock());
    }
}
