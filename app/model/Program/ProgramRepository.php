<?php

namespace App\Model\Program;

use App\ApiModule\DTO\ProgramDetailDTO;
use App\Model\ACL\RoleRepository;
use App\Model\Settings\SettingsRepository;
use App\Model\User\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Query\Expr;
use Kdyby\Doctrine\EntityManager;
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
     * @param $basicBlockDuration
     * @return int[]
     */
    public function findBlockedProgramsIdsByProgram(Program $program, $basicBlockDuration) {
        if (!$program->getBlock())
            return $this->findOverlappingProgramsIds($program, $basicBlockDuration);

        $merged = array_merge($this->findProgramsIdsByBlock($program->getBlock()),
            $this->findOverlappingProgramsIds($program, $basicBlockDuration)
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
     * @param $basicBlockDuration
     * @return int[]
     */
    public function findOverlappingProgramsIds(Program $program, $basicBlockDuration)
    {
        $start = $program->getStart();
        $end = $program->getEnd($basicBlockDuration);

        return $this->createQueryBuilder('p')
            ->select('p.id')
            ->where(
                $this->createQueryBuilder()->expr()->lt(
                    $this->createQueryBuilder()->expr()->max(
                        'p.start',
                        $start
                    ),
                    $this->createQueryBuilder()->expr()->min(
                        $this->createQueryBuilder()->expr()->sum(
                            'p.start',
                            $this->createQueryBuilder()->expr()->prod(
                                'p.duration',
                                $basicBlockDuration
                            )
                        ),
                        $end
                    )
                )
            )
            ->getQuery()
            ->getScalarResult();
    }
}