<?php

declare(strict_types=1);

namespace App\Model\User\CustomInputValue;

use App\Model\EntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;

/**
 * Třída spravující hodnoty vlastních polí přihlášky.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class CustomInputValueRepository extends EntityRepository
{
    /**
     * Vrací hodnotu vlastního pole přihlášky podle id.
     */
    public function findById(?int $id) : ?CustomInputValue
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * Uloží hodnotu vlastního pole přihlášky.
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(CustomInputValue $value) : void
    {
        $this->_em->persist($value);
        $this->_em->flush();
    }

    /**
     * Odstraní hodnotu vlastního pole přihlášky.
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(CustomInputValue $value) : void
    {
        $this->_em->remove($value);
        $this->_em->flush();
    }
}
