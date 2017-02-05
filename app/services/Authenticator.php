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
     * @var EntityManager
     */
    private $em;

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

    public function __construct(EntityManager $em,
                                UserRepository $userRepository,
                                RoleRepository $roleRepository,
                                SkautIsService $skautIsService)
    {
        $this->em = $em;
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

        $user = $this->userRepository->findUserBySkautISUserId($skautISUser->ID);
        $newUser = $user === null;

        if ($newUser) {
            $user = new User($skautISUser->UserName);
            $user->setFirstLogin(new \DateTime());
            $roleUnregistered = $this->roleRepository->findRoleByUntranslatedName(Role::UNREGISTERED);
            $user->addRole($roleUnregistered);
        }

        $this->updateUserFromSkautIS($user, $skautISUser);

        if ($newUser) {
            $user->setVariableSymbol($this->generateVariableSymbol($user->getBirthdate()));
            $this->em->persist($user);
        }

        $this->em->flush();

        $netteRoles = array();
        if ($user->isApproved()) {
            foreach ($user->getRoles() as $role)
                $netteRoles[] = $role->getName();
        }
        else {
            $roleUnapproved = $this->roleRepository->findRoleByUntranslatedName(Role::UNAPPROVED);
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
}