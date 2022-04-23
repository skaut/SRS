<?php

declare(strict_types=1);

namespace App\Model\Application\Repositories;

use App\Model\Application\SubeventsApplication;
use App\Model\Infrastructure\Repositories\AbstractRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Třída spravující přihlášky podakcí.
 */
class SubeventsApplicationRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, SubeventsApplication::class);
    }
}
