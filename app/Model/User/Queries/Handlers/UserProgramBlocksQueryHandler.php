<?php

declare(strict_types=1);

namespace App\Model\User\Queries\Handlers;

use App\Model\Program\Block;
use App\Model\Program\Repositories\BlockRepository;
use App\Model\User\Queries\UserProgramBlocksQuery;
use Doctrine\Common\Collections\Collection;

class UserProgramBlocksQueryHandler
{
    private BlockRepository $blockRepository;

    public function __construct(BlockRepository $blockRepository)
    {
        $this->blockRepository = $blockRepository;
    }

    /**
     * @return Collection<Block>
     */
    public function __invoke(UserProgramBlocksQuery $query) : Collection
    {
        return $this->blockRepository->findUserRegistered($query->getUser(), $query->isIncludeAlternates());
    }
}