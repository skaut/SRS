<?php

namespace App\Model\User;

use Nette;
use Kdyby;

class UserRepository extends Nette\Object
{
    private $em;
    private $userRepository;

    public function __construct(Kdyby\Doctrine\EntityManager $em)
    {
        $this->em = $em;
        $this->userRepository = $em->getRepository(User::class);
    }

}