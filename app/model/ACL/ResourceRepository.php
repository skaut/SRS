<?php

declare(strict_types=1);

namespace App\Model\ACL;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping;
use Kdyby\Doctrine\EntityRepository;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use function array_map;

/**
 * Třída spravující prostředky.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ResourceRepository extends EntityRepository
{
    /** @var Cache */
    private $resourceNamesCache;


    public function __construct(EntityManager $em, Mapping\ClassMetadata $class, IStorage $storage)
    {
        parent::__construct($em, $class);
        $this->resourceNamesCache = new Cache($storage, 'ResourceNames');
    }

    /**
     * Vrací názvy všech prostředků.
     * @return string[]
     * @throws \Throwable
     */
    public function findAllNames() : array
    {
        $names = $this->resourceNamesCache->load(null);
        if ($names === null) {
            $names = $this->createQueryBuilder('r')
                ->select('r.name')
                ->getQuery()
                ->getScalarResult();
            $names = array_map('current', $names);
            $this->resourceNamesCache->save(null, $names);
        }
        return $names;
    }
}
