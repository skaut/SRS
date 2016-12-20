<?php

namespace App\Services;


use App\Model\User\User;
use Nette;
use Nette\Security as NS;
use App\Model\ACL\Role;

class Authenticator extends Nette\Object implements NS\IAuthenticator
{
    /**
     * @var \Kdyby\Doctrine\EntityManager
     */
    private $em;

    /**
     * @var \App\Model\User\UserRepository
     */
    private $userRepository;

    /**
     * @var \App\Model\ACL\RoleRepository
     */
    private $roleRepository;

    /**
     * @var \App\Model\Settings\SettingsRepository
     */
    private $settingsRepository;

    /**
     * @var \Skautis\Skautis
     */
    protected $skautIS;

    public function __construct(\Kdyby\Doctrine\EntityManager $em,
                                \App\Model\User\UserRepository $userRepository,
                                \App\Model\ACL\RoleRepository $roleRepository,
                                \App\Model\Settings\SettingsRepository $settingsRepository,
                                \Skautis\Skautis $skautIS)
    {
        $this->em = $em;
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
        $this->skautIS = $skautIS;
    }

    /**
     * @param array $credentials
     * @return NS\Identity
     */
    function authenticate(array $credentials)
    {
        $skautISUser = $this->skautIS->usr->UserDetail(['ID_Login' => $this->skautIS->getUser()->getLoginId()]);

        $user = $this->userRepository->findUserBySkautISUserIdName($skautISUser->ID);
        $newUser = $user === null;

        if ($newUser) {
            $user = new User($skautISUser->UserName);
            $user->setFirstLogin(new \DateTime("now"));
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

        return new NS\Identity($user->getId(), $netteRoles, ['dbuser' => $user]);
    }

    private function updateUserFromSkautIS(User $user, $skautISUser) {
        $skautISPerson = $this->skautIS->org->PersonDetail(['ID_Login' => $this->skautIS->getUser()->getLoginId(), 'ID' => $skautISUser->ID_Person]);

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
        $user->setLastLogin(new \DateTime("now"));
        $user->setMember($skautISUser->HasMembership);

        $skautISUnitId = $this->skautIS->getUser()->getUnitId();
        if ($skautISUnitId != null)
            $user->setUnit($this->skautIS->org->UnitDetail(['ID_Login' => $this->skautIS->getUser()->getLoginId(), 'ID' => $skautISUnitId])->RegistrationNumber);
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