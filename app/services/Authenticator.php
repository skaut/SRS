<?php

namespace App\Services;

use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\User\User;
use App\Model\User\UserRepository;
use Nette;
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

    /** @var SkautIsService */
    protected $skautIsService;

    /** @var UserRepository */
    private $userRepository;

    /** @var RoleRepository */
    private $roleRepository;

    /** @var FilesService */
    private $filesService;


    /**
     * Authenticator constructor.
     * @param UserRepository $userRepository
     * @param RoleRepository $roleRepository
     * @param SkautIsService $skautIsService
     * @param FilesService $filesService
     */
    public function __construct(UserRepository $userRepository, RoleRepository $roleRepository,
                                SkautIsService $skautIsService, FilesService $filesService)
    {
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
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
                $netteRoles[$role->getId()] = $role->getName();
        } else {
            $roleUnapproved = $this->roleRepository->findBySystemName(Role::UNAPPROVED);
            $netteRoles[$role->getId()] = $roleUnapproved->getName();
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
                $fileName = $photo->ID . $photo->PhotoExtension;
                $path = User::PHOTO_PATH . "/" . $fileName;
                $this->filesService->create($path, $photo->PhotoNormalContent);
                $user->setPhoto($fileName);
            } else {
                $user->setPhoto(NULL);
            }
            $user->setPhotoUpdate($photoUpdate);
        }
    }

    /**
     * Aktualizuje role přihlášeného uživatele.
     * @param $user
     * @param Role $testRole
     */
    public function updateRoles($user, $testRole = NULL)
    {
        $dbuser = $this->userRepository->findById($user->id);

        $netteRoles = [];

        if (!$testRole) {
            if ($dbuser->isApproved()) {
                foreach ($dbuser->getRoles() as $role)
                    $netteRoles[$role->getId()] = $role->getName();
            } else {
                $roleUnapproved = $this->roleRepository->findBySystemName(Role::UNAPPROVED);
                $netteRoles[$role->getId()] = $roleUnapproved->getName();
            }
        } else {
            $netteRoles[0] = Role::TEST;
            $netteRoles[$testRole->getId()] = $testRole->getName();
        }
        
        $user->identity->setRoles($netteRoles);
    }
}
