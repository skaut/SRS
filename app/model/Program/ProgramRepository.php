<?php

namespace App\Model\Program;

use App\ApiModule\DTO\ProgramDetailDTO;
use App\Model\User\User;
use Kdyby\Doctrine\EntityRepository;

class ProgramRepository extends EntityRepository
{
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
    public function findUserAllowed(User $user)
    {
        $allowedCategoriesIds = $this->createQueryBuilder('u')
            ->select('c.id')
            ->join('u.roles', 'r')
            ->join('r.registerableCategories', 'c')
            ->where('u.id = :id')->setParameter('id', $user->getId())
            ->getQuery()
            ->getScalarResult();

        return $this->createQueryBuilder('p')
            ->select('p')
            ->join('p.block', 'b')
            ->leftJoin('b.category', 'c')
            ->where('c.id IN (:ids)')->setParameter('ids', $allowedCategoriesIds)
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

        return $this->createQueryBuilder('p')
            ->select('p.id')
            ->join('p.block', 'b')
            ->where(
                $this->createQueryBuilder()->expr()->lt(
                    $this->createQueryBuilder()->expr()->max(
                        'p.start',
                        $start
                    ),
                    $this->createQueryBuilder()->expr()->min(
                        $this->createQueryBuilder()->expr()->sum(
                            'p.start',
                            'b.duration'
                        ),
                        $end
                    )
                )
            )
            ->getQuery()
            ->getScalarResult();
    }
}