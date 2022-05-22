<?php

declare(strict_types=1);

namespace App\Model\User\Queries\Handlers;

use App\Model\Program\Block;
use App\Model\Program\Repositories\BlockRepository;
use App\Model\User\Queries\UserAllowedBlocksQuery;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class UserAllowedBlocksQueryHandler implements MessageHandlerInterface
{
    public function __construct(private BlockRepository $blockRepository)
    {
    }

    /**
     * @return Collection<int, Block>
     */
    public function __invoke(UserAllowedBlocksQuery $query): Collection
    {
        return $this->blockRepository->findUserAllowed($query->getUser(), $query->isPaidOnly());
    }
}
