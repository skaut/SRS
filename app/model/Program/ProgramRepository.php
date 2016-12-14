<?php

namespace App\Model\Program;

use Nette;
use Kdyby;

class ProgramRepository extends Nette\Object
{
    private $em;
    private $programRepository;

    public function __construct(Kdyby\Doctrine\EntityManager $em)
    {
        $this->em = $em;
        $this->programRepository = $em->getRepository(Program::class);
    }
}