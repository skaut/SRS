<?php

namespace App\Model\Program;

use App\ApiModule\DTO\ProgramDetailDTO;
use App\Model\User\User;
use App\Model\User\UserRepository;
use Doctrine\ORM\Mapping;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\EntityRepository;

class ProgramRepository extends EntityRepository
{
    /** @var UserRepository */
    private $userRepository;

    public function __construct(EntityManager $em, Mapping\ClassMetadata $class, UserRepository $userRepository)
    {
        parent::__construct($em, $class);

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
     * @param User $user
     * @return array
     */
    public function findUserAllowed($user)
    {
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
        $merged = array_merge($this->findProgramsIdsByBlock($program->getBlock()),
            $this->findOverlappingProgramsIds($program)
        );

        if (($key = array_search($program->getId(), $merged)) !== false)
            unset($key);

        return $merged;
    }

    /**
     * @param Block $block
     * @return int[]
     */
    public function findProgramsIdsByBlock(Block $block)
    {
        return $this->createQueryBuilder('p')
            ->select('p.id')
            ->join('p.block', 'b')
            ->where('b.id = :id')->setParameter('id', $block->getId())
            ->getQuery()
            ->getScalarResult();
    }

    /**
     * @param Program $program
     * @return int[]
     */
    public function findOverlappingProgramsIds(Program $program)
    {
        $start = $program->getStart();
        $end = $program->getEnd();

        return $this->createQueryBuilder('p')//(StartA <= EndB)  and  (EndA >= StartB) (StartA <= EndB)  and  (StartB <= EndA)
            ->select('p.id')
            ->join('p.block', 'b')
            ->where("(p.start <= :end) AND (DATE_ADD(p.start, (b.duration * 60), 'second') >= :start)")
            ->orWhere("(p.start <= :end) AND (:start <= (DATE_ADD(p.start, (b.duration * 60), 'second')))")
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getScalarResult();
    }
}