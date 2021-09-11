<?php

declare(strict_types=1);

namespace App\Model\Application\Repositories;

use App\Model\Application\RolesApplication;
use App\Model\Infrastructure\Repositories\AbstractRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Třída spravující přihlášky rolí.
 */
class RolesApplicationRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, RolesApplication::class);
    }
}
