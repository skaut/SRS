<?php

declare(strict_types=1);

namespace App\Model\Structure\Queries\Handlers;

use App\Model\Structure\Queries\SubeventByIdQuery;
use App\Model\Structure\Repositories\SubeventRepository;
use App\Model\Structure\Subevent;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class SubeventByIdQueryHandler implements MessageHandlerInterface
{
    private SubeventRepository $subeventRepository;

    public function __construct(SubeventRepository $subeventRepository)
    {
        $this->subeventRepository = $subeventRepository;
    }

    public function __invoke(SubeventByIdQuery $query): Subevent
    {
        return $this->subeventRepository->findById($query->getId());
    }
}
