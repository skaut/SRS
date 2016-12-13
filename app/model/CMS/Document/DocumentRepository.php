<?php

namespace App\Model\Settings;

use Nette;
use Kdyby;

class DocumentRepository extends Nette\Object //TODO
{
    private $em;
    private $settingsRepository;

    public function __construct(Kdyby\Doctrine\EntityManager $em)
    {
        $this->em = $em;
        $this->settingsRepository = $em->getRepository(Settings::class);
    }
}