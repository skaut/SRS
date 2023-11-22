<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\Acl\Repositories\PermissionRepository;
use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Acl\Repositories\SrsResourceRepository;
use App\Model\Acl\Role;
use App\Model\User\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Nette;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\Localization\Translator;
use Throwable;

use function array_map;

/**
 * Služba pro správu rolí.
 */
class AclService
{
    use Nette\SmartObject;

    private Cache $roleNamesCache;

    private Cache $permissionNamesCache;

    private Cache $resourceNamesCache;

    public function __construct(
        private RoleRepository $roleRepository,
        private PermissionRepository $permissionRepository,
        private SrsResourceRepository $resourceRepository,
        private Translator $translator,
        Storage $storage,
    ) {
        $this->roleNamesCache       = new Cache($storage, 'RoleNames');
        $this->permissionNamesCache = new Cache($storage, 'PermissionNames');
        $this->resourceNamesCache   = new Cache($storage, 'ResourceNames');
    }

    /**
     * Vrací názvy všech rolí.
     *
     * @return string[]
     *
     * @throws Throwable
     */
    public function findAllRoleNames(): array
    {
        $names = $this->roleNamesCache->load(null);
        if ($names === null) {
            $names = $this->roleRepository->createQueryBuilder('r')
                    ->select('r.name')
                    ->getQuery()
                    ->getScalarResult();
            $names = array_map('current', $names);
            $this->roleNamesCache->save(null, $names);
        }

        return $names;
    }

    /**
     * Uloží roli.
     */
    public function saveRole(Role $role): void
    {
        $this->roleRepository->save($role);
        $this->roleNamesCache->clean([Cache::NAMESPACES => ['RoleNames']]);
        $this->permissionNamesCache->clean([Cache::NAMESPACES => ['PermissionNames']]);
    }

    /**
     * Odstraní roli.
     */
    public function removeRole(Role $role): void
    {
        $this->roleRepository->remove($role);
        $this->roleNamesCache->clean([Cache::NAMESPACES => ['RoleNames']]);
    }

    /**
     * Vrací seznam rolí jako možnosti pro select, role specifikovaná parametrem je vynechána.
     *
     * @return string[]
     */
    public function getRolesWithoutRoleOptions(int $roleId): array
    {
        $roles = $this->roleRepository->createQueryBuilder('r')
            ->select('r.id, r.name')
            ->where('r.id != :id')->setParameter('id', $roleId)
            ->orderBy('r.name')
            ->getQuery()
            ->getResult();

        $options = [];
        foreach ($roles as $role) {
            $options[$role['id']] = $role['name'];
        }

        return $options;
    }

    /**
     * Vrací role bez vybraných rolí jako možnosti pro select.
     *
     * @param string[] $withoutRoles
     *
     * @return string[]
     */
    public function getRolesWithoutRolesOptions(array $withoutRoles): array
    {
        if (empty($withoutRoles)) {
            $roles = $this->roleRepository->createQueryBuilder('r')
                ->select('r.id, r.name')
                ->orderBy('r.name')
                ->getQuery()
                ->getResult();
        } else {
            $roles = $this->roleRepository->createQueryBuilder('r')
                ->select('r.id, r.name')
                ->where('r.systemName NOT IN (:roles)')->setParameter('roles', $withoutRoles)
                ->orWhere('r.systemName IS NULL')
                ->orderBy('r.name')
                ->getQuery()
                ->getResult();
        }

        $options = [];
        foreach ($roles as $role) {
            $options[$role['id']] = $role['name'];
        }

        return $options;
    }

    /**
     * Vrací seznam rolí bez vybraných rolí, s informací o obsazenosti, jako možnosti pro select.
     *
     * @param string[] $withoutRoles
     *
     * @return string[]
     */
    public function getRolesWithoutRolesOptionsWithCapacity(array $withoutRoles): array
    {
        if (empty($withoutRoles)) {
            $roles = $this->roleRepository->createQueryBuilder('r')
                ->orderBy('r.name')
                ->getQuery()
                ->getResult();
        } else {
            $roles = $this->roleRepository->createQueryBuilder('r')
                ->where('r.systemName NOT IN (:roles)')->setParameter('roles', $withoutRoles)
                ->orWhere('r.systemName IS NULL')
                ->orderBy('r.name')
                ->getQuery()
                ->getResult();
        }

        $options = [];
        foreach ($roles as $role) {
            if ($role->hasLimitedCapacity()) {
                $options[$role->getId()] = $this->translator->translate('web.common.role_option', null, [
                    'role' => $role->getName(),
                    'occupied' => $role->countUsers(),
                    'total' => $role->getCapacity(),
                ]);
            } else {
                $options[$role->getId()] = $role->getName();
            }
        }

        return $options;
    }

    /**
     * Vrací seznam rolí bez vybraných rolí, s informací o počtu uživatelů, jako možnosti pro select.
     *
     * @param string[] $withoutRoles
     *
     * @return string[]
     */
    public function getRolesWithoutRolesOptionsWithApprovedUsersCount(array $withoutRoles): array
    {
        if (empty($withoutRoles)) {
            $roles = $this->roleRepository->createQueryBuilder('r')
                ->orderBy('r.name')
                ->getQuery()
                ->getResult();
        } else {
            $roles = $this->roleRepository->createQueryBuilder('r')
                ->where('r.systemName NOT IN (:roles)')->setParameter('roles', $withoutRoles)
                ->orWhere('r.systemName IS NULL')
                ->orderBy('r.name')
                ->getQuery()
                ->getResult();
        }

        $options = [];
        foreach ($roles as $role) {
            $options[$role->getId()] = $this->translator->translate(
                'admin.common.role_option',
                $role->countUsers(),
                [
                    'role' => $role->getName(),
                ],
            );
        }

        return $options;
    }

    /**
     * Vrací seznam rolí splňujících podmínku, s informací o obsazenosti, jako možnosti pro select.
     *
     * @return string[]
     */
    public function getRolesOptionsWithCapacity(bool $registerableNowOnly, bool $includeUsers, User|null $user = null): array
    {
        $roles = $this->roleRepository->findFilteredRoles($registerableNowOnly, false, $includeUsers, $user);

        $options = [];
        foreach ($roles as $role) {
            if ($role->hasLimitedCapacity()) {
                $options[$role->getId()] = $this->translator->translate('web.common.role_option', null, [
                    'role' => $role->getName(),
                    'occupied' => $role->countUsers(),
                    'total' => $role->getCapacity(),
                ]);
            } else {
                $options[$role->getId()] = $role->getName();
            }
        }

        return $options;
    }

    /**
     * Vrací názvy všech oprávnění.
     *
     * @return Collection<int, string[]>
     *
     * @throws Throwable
     */
    public function findAllPermissionNames(): Collection
    {
        $names = $this->permissionNamesCache->load(null);
        if ($names === null) {
            $names = $this->permissionRepository->createQueryBuilder('p')
                ->select('p.name')
                ->addSelect('role.name AS roleName')->join('p.roles', 'role')
                ->addSelect('resource.name AS resourceName')->join('p.resource', 'resource')
                ->getQuery()
                ->getResult();
            $this->permissionNamesCache->save(null, $names);
        }

        return new ArrayCollection($names);
    }

    /**
     * Vrací názvy všech prostředků.
     *
     * @return string[]
     *
     * @throws Throwable
     */
    public function findAllResourceNames(): array
    {
        $names = $this->resourceNamesCache->load(null);
        if ($names === null) {
            $names = $this->resourceRepository->createQueryBuilder('r')
                ->select('r.name')
                ->getQuery()
                ->getScalarResult();
            $names = array_map('current', $names);
            $this->resourceNamesCache->save(null, $names);
        }

        return $names;
    }
}
