<?php
declare(strict_types=1);

namespace App\Services;

use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\User\User;
use App\Model\User\UserRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Nette;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Nette\Security as NS;

/**
 * Služba starající se o autentizaci uživatelů.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class Authenticator implements NS\IAuthenticator
{

	use Nette\SmartObject;

	/** @var Cache */
	private $userRolesCache;

	/** @var SkautIsService */
	protected $skautIsService;

	/** @var UserRepository */
	private $userRepository;

	/** @var RoleRepository */
	private $roleRepository;

	/** @var FilesService */
	private $filesService;

	public function __construct(
		UserRepository $userRepository,
		RoleRepository $roleRepository,
		SkautIsService $skautIsService,
		FilesService $filesService,
		IStorage $storage
	)
	{
		$this->userRepository = $userRepository;
		$this->roleRepository = $roleRepository;
		$this->skautIsService = $skautIsService;
		$this->filesService = $filesService;

		$this->userRolesCache = new Cache($storage, 'UserRoles');
	}

	/**
	 * Autentizuje uživatele a případně vytvoří nového.
	 * @param string[] $credentials
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function authenticate(array $credentials): NS\Identity
	{
		$skautISUser = $this->skautIsService->getUserDetail();

		$user = $this->userRepository->findBySkautISUserId($skautISUser->ID);

		$firstLogin = false;
		if ($user === null) {
			$user = new User();
			$roleNonregistered = $this->roleRepository->findBySystemName(Role::NONREGISTERED);
			$user->addRole($roleNonregistered);
			$firstLogin = true;
		}

		$this->updateUserFromSkautIS($user, $skautISUser);

		$this->userRepository->save($user);

		//nacteni schvalenych roli v SRS
		$netteRoles = [];
		if ($user->isApproved()) {
			foreach ($user->getRoles() as $role) {
				$netteRoles[$role->getId()] = $role->getName();
			}
		} else {
			$roleUnapproved = $this->roleRepository->findBySystemName(Role::UNAPPROVED);
			$netteRoles[$roleUnapproved->getId()] = $roleUnapproved->getName();
		}

		//invalidace cache roli ze skautIS
		$this->userRolesCache->remove($user->getSkautISUserId());

		return new NS\Identity($user->getId(), $netteRoles, ['firstLogin' => $firstLogin]);
	}

	/**
	 * Aktualizuje údaje uživatele ze skautIS.
	 * @throws \Exception
	 */
	private function updateUserFromSkautIS(User $user, \stdClass $skautISUser): void
	{
		$skautISPerson = $this->skautIsService->getPersonDetail($skautISUser->ID_Person);

		$user->setUsername($skautISUser->UserName);
		$user->setSkautISUserId($skautISUser->ID);
		$user->setSkautISPersonId($skautISUser->ID_Person);
		$user->setEmail($skautISPerson->Email);
		$user->setFirstName($skautISPerson->FirstName);
		$user->setLastName($skautISPerson->LastName);
		$user->setNickName($skautISPerson->NickName);
		$user->setSex($skautISPerson->ID_Sex);
		$user->setBirthdate(new \DateTime($skautISPerson->Birthday));
		$user->setSecurityCode($skautISUser->SecurityCode);
		$user->setStreet($skautISPerson->Street);
		$user->setCity($skautISPerson->City);
		$user->setPostcode($skautISPerson->Postcode);
		$user->setState($skautISPerson->State);
		$user->setLastLogin(new \DateTime());
		$user->setMember($skautISUser->HasMembership);

		$validMembership = $this->skautIsService->getValidMembership($user->getSkautISPersonId());
		if ($validMembership === null) {
			$user->setUnit(null);
		} else {
			$user->setUnit($validMembership->RegistrationNumber);
		}

		$photoUpdate = new \DateTime($skautISPerson->PhotoUpdate);
		if ($user->getPhotoUpdate() !== null && $photoUpdate->diff($user->getPhotoUpdate())->s < 1) {
			return;
		}

		$photo = $this->skautIsService->getPersonPhoto($skautISUser->ID_Person, 'normal');
		if ($photo->ID_PersonPhotoNormal) {
			$fileName = $photo->ID . $photo->PhotoExtension;
			$path = User::PHOTO_PATH . '/' . $fileName;
			$this->filesService->create($path, $photo->PhotoNormalContent);
			$user->setPhoto($fileName);
		} else {
			$user->setPhoto(null);
		}
		$user->setPhotoUpdate($photoUpdate);
	}

	/**
	 * Aktualizuje role přihlášeného uživatele.
	 */
	public function updateRoles(NS\User $user, ?Role $testedRole = null): void
	{
		$dbuser = $this->userRepository->findById($user->id);

		$netteRoles = [];

		if (!$testedRole) {
			if ($dbuser->isApproved()) {
				foreach ($dbuser->getRoles() as $role) {
					$netteRoles[$role->getId()] = $role->getName();
				}
			} else {
				$roleUnapproved = $this->roleRepository->findBySystemName(Role::UNAPPROVED);
				$netteRoles[$roleUnapproved->getId()] = $roleUnapproved->getName();
			}
		} else {
			$netteRoles[0] = Role::TEST;
			$netteRoles[$testedRole->getId()] = $testedRole->getName();
		}

		$user->identity->setRoles($netteRoles);
	}
}
