<?php

declare(strict_types=1);

namespace App\Model\ACL;

use App\Model\EntityManagerDecorator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;

/**
 *
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class PermissionFacade
{
    /** @var EntityManagerDecorator */
    private $em;

    /** @var PermissionRepository */
    private $permissionRepository;

    /** @var Cache */
    private $permissionNamesCache;

    public function __construct(EntityManagerDecorator $em, IStorage $storage)
    {
        $this->em                   = $em;
        $this->permissionRepository = $em->getRepository(Permission::class);
        $this->permissionNamesCache = new Cache($storage, 'PermissionNames');
    }

    /**
     * Vrací názvy všech oprávnění.
     *
     * @return Collection|string[]
     */
    public function findAllNames() : Collection
    {
        $names = $this->permissionNamesCache->load(null);
        if ($names === null) {
            $names = $result = $this->permissionRepository->createQueryBuilder('p')
                    ->select('p.name')
                    ->addSelect('role.name AS roleName')->join('p.roles', 'role')
                    ->addSelect('resource.name AS resourceName')->join('p.resource', 'resource')
                    ->getQuery()
                    ->getResult();
            $this->permissionNamesCache->save(null, $names);
        }
        return new ArrayCollection($names);
    }
}
