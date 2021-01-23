<?php

declare(strict_types=1);

namespace App\Model\Application;

use App\Model\Acl\Role;
use App\Model\Application\Repositories\ApplicationRepository;
use App\Model\Enums\ApplicationState;
use App\Model\Structure\Subevent;
use App\Model\User\User;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;

class ApplicationFactory
{
    public static function createRolesApplication(
        ApplicationRepository $applicationRepository,
        User $user,
        Role $role,
        string $state = ApplicationState::PAID_FREE
    ): RolesApplication {
        $rolesApplication = new RolesApplication();
        $rolesApplication->setUser($user);
        $rolesApplication->setRoles(new ArrayCollection([$role]));
        $rolesApplication->setFee(0);
        $rolesApplication->setApplicationDate(new DateTimeImmutable());
        $rolesApplication->setState($state);
        $rolesApplication->setValidFrom(new DateTimeImmutable());
        $applicationRepository->save($rolesApplication);
        $rolesApplication->setApplicationId($rolesApplication->getId());
        $applicationRepository->save($rolesApplication);

        return $rolesApplication;
    }

    public static function createSubeventsApplication(
        ApplicationRepository $applicationRepository,
        User $user,
        Subevent $subevent,
        string $state = ApplicationState::PAID
    ): SubeventsApplication {
        $subeventsApplication = new SubeventsApplication();
        $subeventsApplication->setUser($user);
        $subeventsApplication->setSubevents(new ArrayCollection([$subevent]));
        $subeventsApplication->setFee(100);
        $subeventsApplication->setApplicationDate(new DateTimeImmutable());
        $subeventsApplication->setState($state);
        $subeventsApplication->setValidFrom(new DateTimeImmutable());
        $applicationRepository->save($subeventsApplication);
        $subeventsApplication->setApplicationId($subeventsApplication->getId());
        $applicationRepository->save($subeventsApplication);

        return $subeventsApplication;
    }
}
