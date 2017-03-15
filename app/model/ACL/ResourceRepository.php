<?php

namespace App\Model\ACL;

use Kdyby\Doctrine\EntityRepository;

class ResourceRepository extends EntityRepository
{
    /**
     * @return string[]
     */
    public function findAllNames()
    {
        $names = $this->createQueryBuilder('r')
            ->select('r.name')
            ->getQuery()
            ->getScalarResult();
        return array_map('current', $names);
    }
}