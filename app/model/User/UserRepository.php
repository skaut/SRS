<?php

namespace App\Model\User;

use Kdyby\Doctrine\EntityRepository;

class UserRepository extends EntityRepository
{
    public function findUserById($id) {
        return $this->findOneBy(array('id' => $id));
    }

    public function findUserBySkautISUserIdName($skautISUserId) {
        return $this->findOneBy(array('skautISUserId' => $skautISUserId));
    }
}