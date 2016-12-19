<?php

namespace App\Services;


use App\Model\User\User;
use Nette;
use Nette\Security as NS;
use Nette\Security\AuthenticationException;
use Nette\Security\IIdentity;
use App\Model\ACL\Role;

class Authenticator extends Nette\Object implements NS\IAuthenticator {

    /**
     * @var \Kdyby\Doctrine\EntityManager
     */
    protected $em;

    /**
     * @var \Skautis\Skautis
     */
    protected $skautis;

    /**
     * @var \App\Model\User\UserRepository
     */
    protected $userRepository;

    /**
     * @var \App\Model\ACL\RoleRepository
     */
    protected $roleRepository;

    /**
     * Authenticator constructor.
     * @param \Kdyby\Doctrine\EntityManager $em
     * @param \Skautis\Skautis $skautis
     * @param \App\Model\User\UserRepository $userRepository
     * @param \App\Model\ACL\RoleRepository $roleRepository
     */
    public function __construct(\Kdyby\Doctrine\EntityManager $em, \Skautis\Skautis $skautis, \App\Model\User\UserRepository $userRepository, \App\Model\ACL\RoleRepository $roleRepository)
    {
        $this->em = $em;
        $this->skautis = $skautis;
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
    }

    /**
     * @param array $credentials
     */
    function authenticate(array $credentials)
    {
        $skautISUser = $this->skautis->usr->UserDetail(['ID_Login' => $this->skautis->getUser()->getLoginId()]);

        $user = $this->userRepository->findUserBySkautISUserIdName($skautISUser->ID);
        $newUser = $user === null;

        if ($newUser) {
            $user = new User($skautISUser->UserName);
            $user->setFirstLogin(new \DateTime("now"));
            $roleUnregistered = $this->roleRepository->findRoleByName(Role::UNREGISTERED);
            $user->addRole($roleUnregistered);
        }

        $this->updateUserFromSkautIS($user, $skautISUser);

        if ($newUser) {
            $this->em->persist($user);
        }

        $this->em->flush();

        $netteRoles = array();
        if ($user->isApproved()) {
            foreach ($user->getRoles() as $role)
                $netteRoles[] = $role->getName();
        }
        else {
            $roleUnapproved = $this->roleRepository->findRoleByName(Role::UNAPPROVED);
            $netteRoles[] = $roleUnapproved->getName();
        }

        return new NS\Identity($user->getId(), $netteRoles, ['object' => $user]);
    }

    private function updateUserFromSkautIS(User $user, $skautISUser) {
        $skautISPerson = $this->skautis->org->PersonDetail(['ID_Login' => $this->skautis->getUser()->getLoginId(), 'ID' => $skautISUser->ID_Person]);

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

        $skautISUnitId = $this->skautis->getUser()->getUnitId();
        if ($skautISUnitId !== null)
            $user->setUnit($this->skautis->org->UnitDetail(['ID_Login' => $this->skautis->getUser()->getLoginId(), 'ID' => $skautISUnitId])->RegistrationNumber);
        else
            $user->setUnit(null);
    }
}