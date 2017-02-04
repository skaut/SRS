<?php

namespace App\Model\ACL;

use Kdyby\Doctrine\EntityRepository;

class ResourceRepository extends EntityRepository
{
    public function findResourcesNames() {
        return $this->createQueryBuilder('r')->select('r.name')->getQuery()->execute();
    }
}