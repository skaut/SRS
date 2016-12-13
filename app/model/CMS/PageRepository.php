<?php

namespace App\Model\CMS;

use Nette;
use Kdyby;

class PageRepository extends Nette\Object
{
    private $em;
    private $pageRepository;

    public function __construct(Kdyby\Doctrine\EntityManager $em)
    {
        $this->em = $em;
        $this->pageRepository = $em->getRepository(Page::class);
    }
}