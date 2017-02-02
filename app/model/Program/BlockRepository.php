<?php

namespace App\Model\Program;

use Kdyby\Doctrine\EntityRepository;

class BlockRepository extends EntityRepository
{
    public function findBlockById($id) {
        return $this->find($id);
    }
}