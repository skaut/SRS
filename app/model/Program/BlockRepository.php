<?php

namespace App\Model\Program;

use Kdyby\Doctrine\EntityRepository;

class BlockRepository extends EntityRepository
{
    public function findBlockById($id) {
        return $this->find($id);
    }

    public function removeBlock($id)
    {
        $block = $this->find($id);
        $this->_em->remove($block);
        $this->_em->flush();
    }
}