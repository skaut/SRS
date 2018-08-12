<?php
declare(strict_types=1);

namespace App\Model\User\CustomInputValue;

use Kdyby\Doctrine\EntityRepository;


/**
 * Třída spravující hodnoty vlastních polí přihlášky.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class CustomInputValueRepository extends EntityRepository
{
    /**
     * Vrací hodnotu vlastního pole přihlášky podle id.
     * @param $id
     * @return CustomInputValue|null
     */
    public function findById($id)
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * Uloží hodnotu vlastního pole přihlášky.
     * @param CustomInputValue $value
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(CustomInputValue $value)
    {
        $this->_em->persist($value);
        $this->_em->flush();
    }

    /**
     * Odstraní hodnotu vlastního pole přihlášky.
     * @param CustomInputValue $value
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function remove(CustomInputValue $value)
    {
        $this->_em->remove($value);
        $this->_em->flush();
    }
}
