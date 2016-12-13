<?php

namespace App\Model\ACL;

use Nette;
use Kdyby;

class PermissionRepository extends Nette\Object
{
    private $em;
    private $permissionRepository;

    public function __construct(Kdyby\Doctrine\EntityManager $em)
    {
        $this->em = $em;
        $this->permissionRepository = $em->getRepository(Permission::class);
    }
}