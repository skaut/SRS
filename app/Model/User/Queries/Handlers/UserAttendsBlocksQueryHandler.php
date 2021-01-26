<?php

declare(strict_types=1);

namespace App\Model\User\Queries\Handlers;

use App\Model\Program\Block;
use App\Model\Program\Repositories\BlockRepository;
use App\Model\User\Queries\UserAttendsBlocksQuery;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class UserAttendsBlocksQueryHandler implements MessageHandlerInterface
{
    private BlockRepository $blockRepository;

    public function __construct(BlockRepository $blockRepository)
    {
        $this->blockRepository = $blockRepository;
    }

    /**
     * @return Collection<Block>
     */
    public function __invoke(UserAttendsBlocksQuery $query): Collection
    {
        return $this->blockRepository->findUserAttends($query->getUser());
    }
}
