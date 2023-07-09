<?php

declare(strict_types=1);

namespace App\Model\Structure\Queries\Handlers;

use App\Model\Structure\Queries\SubeventByIdQuery;
use App\Model\Structure\Repositories\SubeventRepository;
use App\Model\Structure\Subevent;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class SubeventByIdQueryHandler implements MessageHandlerInterface
{
    public function __construct(private SubeventRepository $subeventRepository)
    {
    }

    public function __invoke(SubeventByIdQuery $query): Subevent|null
    {
        return $this->subeventRepository->findById($query->getId());
    }
}
