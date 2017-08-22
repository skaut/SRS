<?php

namespace App\Services;

use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsRepository;
use App\Model\User\User;
use App\Model\User\UserRepository;
use Nette;
use Nette\Security as NS;


/**
 * Služba starající se o autentizaci uživatelů.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class Authenticator extends Nette\Object implements NS\IAuthenticator
{
    /** @var UserRepository */
    private $userRepository;

    /** @var RoleRepository */
    private $roleRepository;

    /** @var SettingsRepository */
    private $settingsRepository;

    /** @var SkautIsService */
    protected $skautIsService;

    /** @var FilesService */
    private $filesService;


    /**
     * Authenticator constructor.
     * @param UserRepository $userRepository
     * @param RoleRepository $roleRepository
     * @param SettingsRepository $settingsRepository
     * @param SkautIsService $skautIsService
     * @param FilesService $filesService
     */
    public function __construct(UserRepository $userRepository, RoleRepository $roleRepository,
                                SettingsRepository $settingsRepository, SkautIsService $skautIsService,
                                FilesService $filesService)
    {
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
        $this->settingsRepository = $settingsRepository;
        $this->skautIsService = $skautIsService;
        $this->filesService = $filesService;
    }

    /**
     * Autentizuje uživatele a případně vytvoří nového.
     * @param array $credentials
     * @return NS\Identity
     */
    public function authenticate(array $credentials)
    {
        $skautISUser = $this->skautIsService->getUserDetail();

        $user = $this->userRepository->findBySkautISUserId($skautISUser->ID);

        $firstLogin = FALSE;
        if ($user === NULL) {
            $user = new User();
            $roleNonregistered = $this->roleRepository->findBySystemName(Role::NONREGISTERED);
            $user->addRole($roleNonregistered);
            $firstLogin = TRUE;
        }

        $this->updateUserFromSkautIS($user, $skautISUser);

        $this->userRepository->save($user);

        $netteRoles = [];
        if ($user->isApproved()) {
            foreach ($user->getRoles() as $role)
                $netteRoles[] = $role->getName();
        } else {
            $roleUnapproved = $this->roleRepository->findBySystemName(Role::UNAPPROVED);
            $netteRoles[] = $roleUnapproved->getName();
        }

        return new NS\Identity($user->getId(), $netteRoles, ['firstLogin' => $firstLogin]);
    }

    /**
     * Aktualizuje údaje uživatele ze skautIS.
     * @param User $user
     * @param $skautISUser
     */
    private function updateUserFromSkautIS(User $user, $skautISUser)
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
        if ($validMembership == NULL)
            $user->setUnit(NULL);
        else
            $user->setUnit($validMembership->RegistrationNumber);

        $photoUpdate = new \DateTime($skautISPerson->PhotoUpdate);
        if ($user->getPhotoUpdate() === NULL || $photoUpdate->diff($user->getPhotoUpdate())->s >= 1) {
            $photo = $this->skautIsService->getPersonPhoto($skautISUser->ID_Person, "normal");
            if ($photo->ID_PersonPhotoNormal) {
                $fileName = $user->getId() . $photo->PhotoExtension;
                $path = User::PHOTO_PATH . "/" . $fileName;
                $this->filesService->create($path, $photo->PhotoNormalContent);
                $user->setPhoto($fileName);
            }
            else {
                $user->setPhoto(NULL);
            }
            $user->setPhotoUpdate($photoUpdate);
        }
    }

    /**
     * Aktualizuje role přihlášeného uživatele.
     * @param $user
     * @param null $testRole
     */
    public function updateRoles($user, $testRole = NULL)
    {
        $dbuser = $this->userRepository->findById($user->id);

        $netteRoles = [];

        if (!$testRole) {
            if ($dbuser->isApproved()) {
                foreach ($dbuser->getRoles() as $role)
                    $netteRoles[] = $role->getName();
            } else {
                $roleUnapproved = $this->roleRepository->findBySystemName(Role::UNAPPROVED);
                $netteRoles[] = $roleUnapproved->getName();
            }
        } else {
            $netteRoles[] = Role::TEST;
            $netteRoles[] = $testRole->getName();
        }

        $user->identity->setRoles($netteRoles);
    }
}
