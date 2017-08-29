<?php

namespace App\Model\Structure;

use Kdyby\Doctrine\EntityRepository;


/**
 * Třída spravující podakce.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SubeventRepository extends EntityRepository
{
    /**
     * Vrací podakci podle id.
     * @param $id
     * @return Subevent|null
     */
    public function findById($id)
    {
        return $this->findOneBy(['id' => $id]);
    }
}
