<?php

namespace App\Model\ACL;

use Nette;
use Kdyby;

class ResourceRepository extends Nette\Object
{
    private $em;
    private $resourceRepository;

    public function __construct(Kdyby\Doctrine\EntityManager $em)
    {
        $this->em = $em;
        $this->resourceRepository = $em->getRepository(Resource::class);
    }
}