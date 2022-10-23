<?php

declare(strict_types=1);

namespace App\Model\User\Repositories;

use App\Model\Infrastructure\Repositories\AbstractRepository;
use App\Model\User\Patrol;
use App\Model\User\Troop;
use App\Model\User\User;
use App\Model\User\UserGroupRole;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Třída spravující uživatel-skupina-role.
 */
class UserGroupRoleRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, UserGroupRole::class);
    }

    public function findByUserAndPatrol(User $user, Patrol $patrol)
    {
        return $this->getRepository()->findBy(['user' => $user, 'patrol' => $patrol]);
    }

    public function findByUserAndTroop(User $user, Troop $troop)
    {
        return $this->getRepository()->findBy(['user' => $user, 'troop' => $troop]);
    }

    public function save(UserGroupRole $userGroupRole): void
    {
        $this->em->persist($userGroupRole);
        $this->em->flush();
    }
}
