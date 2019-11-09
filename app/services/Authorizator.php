<?php
declare(strict_types=1);

namespace App\Services;

use App\Model\ACL\PermissionFacade;
use App\Model\ACL\ResourceFacade;
use App\Model\ACL\Role;
use App\Model\ACL\RoleFacade;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Nette;

/**
 * Služba nastavující role a oprávnění.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class Authorizator extends Nette\Security\Permission
{

	public function __construct(
		RoleFacade $roleFacade,
		PermissionFacade $permissionRepository,
		ResourceFacade $resourceFacade
	)
	{
		$this->addRole(Role::TEST); //role pouzivana pri testovani jine role

		try {
			foreach ($resourceFacade->findAllNames() as $resourceName) {
				$this->addResource($resourceName);
			}
			foreach ($roleFacade->findAllNames() as $roleName) {
				$this->addRole($roleName);
			}
			foreach ($permissionRepository->findAllNames() as $permission) {
				$this->allow($permission['roleName'], $permission['resourceName'], $permission['name']);
			}
		} catch (TableNotFoundException $ex) {
			//prvni spusteni pred vytvorenim databaze
		}
	}
}
