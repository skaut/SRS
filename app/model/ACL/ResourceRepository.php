<?php

namespace App\Model\ACL;

use Kdyby\Doctrine\EntityRepository;

class ResourceRepository extends EntityRepository
{
    /**
     * @return array
     */
    public function findAllNames() {
        return $this->createQueryBuilder('r')
            ->select('r.name')
            ->getQuery()
            ->getResult();
    }
}