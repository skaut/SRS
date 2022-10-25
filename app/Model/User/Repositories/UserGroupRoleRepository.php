<?php

declare(strict_types=1);

namespace App\Model\User\Repositories;

use App\Model\Infrastructure\Repositories\AbstractRepository;
use App\Model\User\UserGroupRole;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    /**
     * @return Collection<int, UserGroupRole>
     */
    public function findByUserAndPatrol(int $user_id, int $patrol_id): Collection
    {
        $result = $this->getRepository()->findBy(['user' => $user_id, 'patrol' => $patrol_id]);

        return new ArrayCollection($result);
    }

    /**
     * @return Collection<int, UserGroupRole>
     */
    public function findByUserAndTroop(int $user_id, int $troop_id): Collection
    {
        $result = $this->getRepository()->findBy(['user' => $user_id, 'troop' => $troop_id]);

        return new ArrayCollection($result);
    }

    public function save(UserGroupRole $userGroupRole): void
    {
        $this->em->persist($userGroupRole);
        $this->em->flush();
    }

    public function remove(UserGroupRole $userGroupRole): void
    {
        $this->em->remove($userGroupRole);
        $this->em->flush();
    }
}
