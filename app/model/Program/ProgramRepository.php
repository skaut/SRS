<?php

namespace App\Model\Program;

use App\ApiModule\DTO\ProgramDetailDTO;
use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Model\User\User;
use App\Model\User\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\EntityRepository;

class ProgramRepository extends EntityRepository
{
    /** @var UserRepository */
    private $userRepository;

    /**
     * @param UserRepository $userRepository
     */
    public function injectUserRepository(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * @param $id
     * @return Program|null
     */
    public function findById($id)
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * @param Program $program
     */
    public function save(Program $program) {
        $this->_em->persist($program);
        $this->_em->flush();
    }

    /**
     * @param Program $program
     */
    public function remove(Program $program)
    {
        $this->_em->remove($program);
        $this->_em->flush();
    }

    /**
     * @param $programs
     * @return array
     */
    public function findProgramsIds($programs)
    {
        return array_map(function ($o) {
            return $o->getId();
        }, $programs->toArray());
    }

    /**
     * @param User $user
     * @return array
     */
    public function findUserAllowed($user)
    {
        if (!$user->isAllowed(Resource::PROGRAM, Permission::CHOOSE_PROGRAMS))
            return [];

        $registerableCategoriesIds = $this->userRepository->findRegisterableCategoriesIdsByUser($user);

        return $this->createQueryBuilder('p')
            ->select('p')
            ->join('p.block', 'b')
            ->leftJoin('b.category', 'c')
            ->where('c.id IN (:ids)')->setParameter('ids', $registerableCategoriesIds)
            ->orWhere('b.category IS NULL')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param Program $program
     * @return int[]
     */
    public function findBlockedProgramsIdsByProgram(Program $program) {
        return array_merge(
            $this->findOtherProgramsWithSameBlockIds($program),
            $this->findOverlappingProgramsIds($program)
        );
    }

    /**
     * @param Program $program
     * @return int[]
     */
    public function findOtherProgramsWithSameBlockIds(Program $program)
    {
        $programs =  $this->createQueryBuilder('p')
            ->select('p.id')
            ->join('p.block', 'b')
            ->where('b.id = :bid')->setParameter('bid', $program->getBlock()->getId())
            ->andWhere('p.id != :pid')->setParameter('pid', $program->getId())
            ->getQuery()
            ->getScalarResult();
        return array_map('intval', array_map('current', $programs));
    }

    /**
     * @param Program $program
     * @return int[]
     */
    public function findOverlappingProgramsIds(Program $program)
    {
        $start = $program->getStart();
        $end = $program->getEnd();

        $programs = $this->createQueryBuilder('p')
            ->select('p.id')
            ->join('p.block', 'b')
            ->where($this->createQueryBuilder()->expr()->orX(
                "(p.start < :end) AND (DATE_ADD(p.start, (b.duration * 60), 'second') > :start)",
                "(p.start < :end) AND (:start < (DATE_ADD(p.start, (b.duration * 60), 'second')))"
            ))
            ->andWhere('p.id != :pid')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('pid', $program->getId())
            ->getQuery()
            ->getScalarResult();
        return array_map('intval', array_map('current', $programs));
    }

    public function hasOverlappingProgram(Program $program, \DateTime $start, \DateTime $end) {
        $qb = $this->createQueryBuilder('p')
            ->select('p.id')
            ->join('p.block', 'b')
            ->where($this->createQueryBuilder()->expr()->orX(
                "(p.start < :end) AND (DATE_ADD(p.start, (b.duration * 60), 'second') > :start)",
                "(p.start < :end) AND (:start < (DATE_ADD(p.start, (b.duration * 60), 'second')))"
            ))
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        if ($program->getId()) {
            $qb = $qb
                ->andWhere('p.id != :pid')
                ->setParameter('pid', $program->getId());
        }

        return !empty($qb->getQuery()->getResult());
    }

    public function hasOverlappingAutoRegisterProgram(Program $program, \DateTime $start, \DateTime $end) {
        $qb = $this->createQueryBuilder('p')
            ->select('p.id')
            ->join('p.block', 'b')
            ->where($this->createQueryBuilder()->expr()->orX(
                "(p.start < :end) AND (DATE_ADD(p.start, (b.duration * 60), 'second') > :start)",
                "(p.start < :end) AND (:start < (DATE_ADD(p.start, (b.duration * 60), 'second')))"
            ))
            ->andWhere('b.mandatory = 2')
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        if ($program->getId()) {
            $qb = $qb
                ->andWhere('p.id != :pid')
                ->setParameter('pid', $program->getId());
        }

        return !empty($qb->getQuery()->getResult());
    }

    /**
     * @param User $user
     */
    public function updateUserPrograms(User $user) {
        $this->updateUsersPrograms([$user]);
    }

    /**
     * @param User[] $users
     */
    public function updateUsersPrograms(array $users) {
        foreach ($users as $user) {
            $oldUsersPrograms = $user->getPrograms();
            $userAllowedPrograms = $this->findUserAllowed($user);

            $newUsersPrograms = new ArrayCollection();

            foreach ($userAllowedPrograms as $userAllowedProgram) {
                if ($userAllowedProgram->getBlock()->getMandatory() == 2 || $oldUsersPrograms->contains($userAllowedProgram))
                    $newUsersPrograms->add($userAllowedProgram);
            }

            $oldUsersPrograms->clear();
            $user->setPrograms($newUsersPrograms);
        }
    }
}