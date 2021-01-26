<?php

declare(strict_types=1);

namespace App\Model\Acl\Repositories;

use App\Model\Acl\SrsResource;
use App\Model\Infrastructure\Repositories\AbstractRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Třída spravující prostředky.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SrsResourceRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, SrsResource::class);
    }
}
