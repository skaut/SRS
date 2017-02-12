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
    /** @var int */
    private $basicBlockDuration;

    /** @var RoleRepository */
    private $roleRepository;

    public function __construct(EntityManager $em, Mapping\ClassMetadata $class, RoleRepository $roleRepository,
                                SettingsRepository $settingsRepository)
    {
        parent::__construct($em, $class);

        $this->roleRepository = $roleRepository;

        $this->basicBlockDuration = $settingsRepository->getValue('basic_block_duration');
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
    public function remove(Program $program) {
        $this->_em->remove($program);
        $this->_em->flush();
    }

    /**
     * @param null $user
     * @param null $dbuser
     * @param bool $onlyAssigned
     * @param bool $userAllowed
     * @return ProgramDetailDTO[]
     */
    public function findProgramsForSchedule($user = null, $dbuser = null, $onlyAssigned = false, $userAllowed = false)
    {
        if ($onlyAssigned) {
            $programs = new ArrayCollection($this->findAll());
        }
        else {
            $programs = new ArrayCollection($this->createQueryBuilder('p')
                ->where('p.block IS NOT NULL')
                ->getQuery()
                ->getResult()
            );
        }

        foreach ($programs as $program) {
            if ($dbuser) {
                $blocksPrograms = array_merge(
                    $this->findProgramsWithSameBlockIds($program),
                    $this->findProgramsInSameTimeIds($program)
                );
            }
            else {
                $blocksPrograms = [];
            }
        }

        if ($userAllowed) {
            $categories = [];
            $categories[] = null; //nezarazene

            foreach ($user->getIdentity()->getRoles() as $roleName) {
                $role = $this->roleRepository->findByName($roleName);
                foreach ($role->getRegisterableCategories() as $category)
                    $categories[] = $category;
            }

            $criteria = Criteria::expr()->in('category', $categories);
            $programs = $programs->matching($criteria);
        }

        $programDTOs = [];
        foreach ($programs as $program) {
            $programDTOs[] = $program->convertToProgramDTO($this->basicBlockDuration, $dbuser, $blocksPrograms);
        }
        return $programDTOs;
    }

    public function saveProgramFromSchedule($data)
    {
        $data = json_decode($data);
        $data = (array)$data;

        json_last_error();
        $data['start'] = $data['startJSON'];
        if (isset($data['block']->id)) {
            $data['block'] = $data['block']->id;
        }

        $exists = isset($data['id']);
        if ($exists == true) {
            $program = $this->_em->getRepository($this->_entityName)->find($data['id']);
        } else {
            $program = new \SRS\Model\Program\Program();

        }
        $start = \DateTime::createFromFormat("Y-n-j G:i:s", $data['startJSON']);
        $end = \DateTime::createFromFormat("Y-n-j G:i:s", $data['endJSON']);
        $sinceStart = $start->diff($end);
        $minutes = $sinceStart->days * 24 * 60;
        $minutes += $sinceStart->h * 60;
        $minutes += $sinceStart->i;

        $program->setProperties($data, $this->_em);
        $program->duration = $minutes / $basicBlockDuration;
        $this->_em->persist($program);
        $this->_em->flush();
        return $program;
    }

    private function findProgramsInSameTimeIds(Program $program)
    {
        $programIds = [];

        foreach ($this->findAll() as $p) {
            $pId = $p->getId();

            if ($pId == $program->getId())
                continue;

            $pStart = $p->getStart();
            $pEnd = $p->getEnd($this->basicBlockDuration);

            $programStart = $program->getStart();
            $programEnd = $program->getEnd($this->basicBlockDuration);

            if ($pStart == $programStart) {
                $programIds[] = $pId;
                continue;
            }

            if ($pStart > $programStart &&
                $pStart < $programEnd) {
                $programIds[] = $pId;
                continue;
            }

            if ($pEnd > $programStart &&
                $pEnd < $programEnd) {
                $programIds[] = $pId;
                continue;
            }

            if ($pStart < $programStart &&
                $pEnd > $programEnd) {
                $programIds[] = $pId;
                continue;
            }
        }

        return $programIds;
    }

    private function findProgramsWithSameBlockIds(Program $program)
    {
        $programs = $this->createQueryBuilder('p')
            ->select('p.id')
            ->join('p.block', 'b')
            ->where('p.id != :pid')->setParameter('pid', $program->getId())
            ->andWhere('b.id = :bid')->setParameter('bid', $program->getBlock()->getId())
            ->getQuery()
            ->getResult();
        return array_map('current', $programs);
    }


}