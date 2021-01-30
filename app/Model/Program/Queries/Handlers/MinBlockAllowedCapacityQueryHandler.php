<?php

declare(strict_types=1);

namespace App\Model\Program\Queries\Handlers;

use App\Model\Program\Queries\MinBlockAllowedCapacityQuery;
use App\Model\Program\Repositories\BlockRepository;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class MinBlockAllowedCapacityQueryHandler implements MessageHandlerInterface
{
    private BlockRepository $blockRepository;

    public function __construct(BlockRepository $blockRepository)
    {
        $this->blockRepository = $blockRepository;
    }

    public function __invoke(MinBlockAllowedCapacityQuery $query): ?int
    {
        return $this->blockRepository->getMinBlockAllowedCapacity($query->getBlock());
    }
}
