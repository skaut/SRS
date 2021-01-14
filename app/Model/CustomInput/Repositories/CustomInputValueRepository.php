<?php

declare(strict_types=1);

namespace App\Model\CustomInput\Repositories;

use App\Model\CustomInput\CustomInputValue;
use Doctrine\ORM\EntityRepository;
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
    public function findById(?int $id): ?CustomInputValue
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * Uloží hodnotu vlastního pole přihlášky.
     *
     * @throws ORMException
     */
    public function save(CustomInputValue $value): void
    {
        $this->_em->persist($value);
        $this->_em->flush();
    }

    /**
     * Odstraní hodnotu vlastního pole přihlášky.
     *
     * @throws ORMException
     */
    public function remove(CustomInputValue $value): void
    {
        $this->_em->remove($value);
        $this->_em->flush();
    }
}
