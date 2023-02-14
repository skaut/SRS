<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Acl\Role;
use App\Model\User\Repositories\UserRepository;
use App\Model\User\User;
use DateTimeImmutable;
use Doctrine\ORM\ORMException;
use Exception;
use Nette;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\Security as NS;
use Nette\Security\SimpleIdentity;
use stdClass;

use function assert;

/**
 * Služba starající se o autentizaci uživatelů.
 */
class Authenticator implements Nette\Security\Authenticator
{
    use Nette\SmartObject;

    private Cache $userRolesCache;

    public function __construct(
        private UserRepository $userRepository,
        private RoleRepository $roleRepository,
        protected SkautIsService $skautIsService,
        private FilesService $filesService,
        Storage $storage
    ) {
        $this->userRolesCache = new Cache($storage, 'UserRoles');
    }

    /**
     * Autentizuje uživatele a případně vytvoří nového.
     *
     * @throws ORMException
     * @throws Exception
     */
    public function authenticate(string $user, string $password): SimpleIdentity
    {
        $skautISUser = $this->skautIsService->getUserDetail();

        $user = $this->userRepository->findBySkautISUserId($skautISUser->ID);

        $firstLogin = false;
        if ($user === null) {
            // nacten ze skautIS pres skupinu
            $user              = $this->userRepository->findBySkautISPersonId($skautISUser->ID_Person) ?? new User();
            $roleNonregistered = $this->roleRepository->findBySystemName(Role::NONREGISTERED);
            $user->addRole($roleNonregistered);
            $firstLogin = true;
        }

        $this->updateUserFromSkautIS($user, $skautISUser);

        $this->userRepository->save($user);

        // nacteni schvalenych roli v SRS
        $netteRoles = [];

        foreach ($user->getConfirmedGroupRoles() as $groupRole) {
            $netteRoles[$groupRole->getRole()->getId()] = $groupRole->getRole()->getName();
        }

        if ($user->isApproved()) {
            foreach ($user->getRoles() as $role) {
                $netteRoles[$role->getId()] = $role->getName();
            }
        } else {
            $roleUnapproved                       = $this->roleRepository->findBySystemName(Role::UNAPPROVED);
            $netteRoles[$roleUnapproved->getId()] = $roleUnapproved->getName();
        }

        // invalidace cache roli ze skautIS
        $this->userRolesCache->remove($user->getSkautISUserId());

        return new SimpleIdentity($user->getId(), $netteRoles, ['firstLogin' => $firstLogin]);
    }

    /**
     * Aktualizuje údaje uživatele ze skautIS.
     *
     * @throws Exception
     */
    private function updateUserFromSkautIS(User $user, stdClass $skautISUser): void
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
        $user->setBirthdate(new DateTimeImmutable($skautISPerson->Birthday));
        $user->setSecurityCode($skautISUser->SecurityCode);
        $user->setStreet($skautISPerson->Street);
        $user->setCity($skautISPerson->City);
        $user->setPostcode($skautISPerson->Postcode);
        $user->setState($skautISPerson->State);
        $user->setLastLogin(new DateTimeImmutable());
        $user->setMember($skautISUser->HasMembership);

        $validMembership = $this->skautIsService->getValidMembership($user->getSkautISPersonId());
        $user->setUnit($validMembership?->RegistrationNumber);

        $photoUpdate = new DateTimeImmutable($skautISPerson->PhotoUpdate);
        if ($user->getPhotoUpdate() === null || $photoUpdate->diff($user->getPhotoUpdate())->s > 0) {
            $photo = $this->skautIsService->getPersonPhoto($skautISUser->ID_Person, 'normal');
            if ($photo->ID_PersonPhotoNormal) {
                $fileName = $photo->ID . $photo->PhotoExtension;
                $path     = $this->filesService->create($photo->PhotoNormalContent, User::PHOTO_PATH, false, $fileName);
                $user->setPhoto($path);
            } else {
                $user->setPhoto(null);
            }

            $user->setPhotoUpdate($photoUpdate);
        }
    }

    /**
     * Aktualizuje role přihlášeného uživatele.
     */
    public function updateRoles(NS\User $user, ?Role $testedRole = null): void
    {
        $dbuser = $this->userRepository->findById($user->id);

        $netteRoles = [];

        if (! $testedRole) {
            if ($dbuser->isApproved()) {
                foreach ($dbuser->getRoles() as $role) {
                    $netteRoles[$role->getId()] = $role->getName();
                }
            } else {
                $roleUnapproved                       = $this->roleRepository->findBySystemName(Role::UNAPPROVED);
                $netteRoles[$roleUnapproved->getId()] = $roleUnapproved->getName();
            }
        } else {
            $netteRoles[0]                    = Role::TEST;
            $netteRoles[$testedRole->getId()] = $testedRole->getName();
        }

        $identity = $user->identity;
        assert($identity instanceof SimpleIdentity);
        $identity->setRoles($netteRoles);
    }
}
