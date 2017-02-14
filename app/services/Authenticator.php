<?php

namespace App\Services;


use App\Model\ACL\RoleRepository;
use App\Model\Settings\SettingsRepository;
use App\Model\User\User;
use App\Model\User\UserRepository;
use Kdyby\Doctrine\EntityManager;
use Nette;
use Nette\Security as NS;
use App\Model\ACL\Role;

class Authenticator extends Nette\Object implements NS\IAuthenticator
{
    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var RoleRepository
     */
    private $roleRepository;

    /**
     * @var SkautIsService
     */
    protected $skautIsService;

    public function __construct(UserRepository $userRepository, RoleRepository $roleRepository,
                                SkautIsService $skautIsService)
    {
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
        $this->skautIsService = $skautIsService;
    }

    /**
     * @param array $credentials
     * @return NS\Identity
     */
    function authenticate(array $credentials)
    {
        $skautISUser = $this->skautIsService->getUserDetail();

        $user = $this->userRepository->findBySkautISUserId($skautISUser->ID);
        $newUser = $user === null;

        if ($newUser) {
            $user = new User($skautISUser->UserName);
            $user->setFirstLogin(new \DateTime());
            $roleNonregistered = $this->roleRepository->findBySystemName(Role::NONREGISTERED);
            $user->addRole($roleNonregistered);
        }

        $this->updateUserFromSkautIS($user, $skautISUser);

        if ($newUser) {
            $user->setVariableSymbol($this->generateVariableSymbol($user->getBirthdate()));
        }

        $this->userRepository->save($user);

        $netteRoles = [];
        if ($user->isApproved()) {
            foreach ($user->getRoles() as $role)
                $netteRoles[] = $role->getName();
        }
        else {
            $roleUnapproved = $this->roleRepository->findBySystemName(Role::UNAPPROVED);
            $netteRoles[] = $roleUnapproved->getName();
        }

        return new NS\Identity($user->getId(), $netteRoles);
    }

    private function updateUserFromSkautIS(User $user, $skautISUser) {
        $skautISPerson = $this->skautIsService->getPersonDetail($skautISUser->ID_Person);

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

        $skautISUnitId = $this->skautIsService->getUnitId();
        if ($skautISUnitId != null)
            $user->setUnit($this->skautIsService->getUnitDetail($skautISUnitId)->RegistrationNumber);
        else
            $user->setUnit(null);
    }

    private function generateVariableSymbol(\DateTime $birthDate) {
        $variableSymbol = $birthDate->format('ymd');

        while ($this->userRepository->variableSymbolExists($variableSymbol))
            $variableSymbol++;

        return $variableSymbol;
    }

    public function updateRoles($user, $testRole = null) {
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
        }
        else {
            $netteRoles[] = Role::TEST;
            $netteRoles[] = $testRole->getName();
        }

        $user->identity->setRoles($netteRoles);
    }
}