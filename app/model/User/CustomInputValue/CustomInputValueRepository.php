<?php

namespace App\Model\User\CustomInputValue;

use Kdyby\Doctrine\EntityRepository;

class CustomInputValueRepository extends EntityRepository
{
    /**
     * @param $id
     * @return CustomInputValue|null
     */
    public function findById($id)
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * @param CustomInputValue $value
     */
    public function save(CustomInputValue $value)
    {
        $this->_em->persist($value);
        $this->_em->flush();
    }

    /**
     * @param CustomInputValue $value
     */
    public function remove(CustomInputValue $value)
    {
        $this->_em->remove($value);
        $this->_em->flush();
    }
}