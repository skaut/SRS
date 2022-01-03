<?php

declare(strict_types=1);

namespace App\Model\Structure\Queries\Handlers;

use App\Model\Structure\Queries\SubeventsQuery;
use App\Model\Structure\Repositories\SubeventRepository;
use App\Model\Structure\Subevent;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class SubeventsQueryHandler implements MessageHandlerInterface
{
    private SubeventRepository $subeventRepository;

    public function __construct(SubeventRepository $subeventRepository)
    {
        $this->subeventRepository = $subeventRepository;
    }

    /**
     * @return Collection<int, Subevent>
     */
    public function __invoke(SubeventsQuery $query): Collection
    {
        return $this->subeventRepository->findFilteredSubevents($query->isExplicitOnly(), $query->isRegisterableNowOnly(), $query->isUserNotRegisteredOnly(), $query->isIncludeUserRegistered(), $query->getUser());
    }
}
