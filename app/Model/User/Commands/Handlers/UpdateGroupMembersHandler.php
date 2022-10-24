<?php

declare(strict_types=1);

namespace App\Model\User\Commands\Handlers;

use App\Model\Acl\Repositories\RoleRepository;
use App\Model\User\Commands\UpdateGroupMembers;
use App\Model\User\Patrol;
use App\Model\User\Queries\PatrolByIdQuery;
use App\Model\User\Queries\TroopByIdQuery;
use App\Model\User\Repositories\PatrolRepository;
use App\Model\User\Repositories\UserGroupRoleRepository;
use App\Model\User\Repositories\UserRepository;
use App\Model\User\User;
use App\Model\User\UserGroupRole;
use App\Services\QueryBus;
use App\Services\SkautIsService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

use function property_exists;
use function sprintf;

class UpdateGroupMembersHandler implements MessageHandlerInterface
{
    public function __construct(
        private QueryBus $queryBus,
        private EntityManagerInterface $em,
        private SkautIsService $skautIsService,
        private UserRepository $userRepository,
        private PatrolRepository $patrolRepository,
        private RoleRepository $roleRepository,
        private UserGroupRoleRepository $userGroupRoleRepository
    ) {
    }

    public function __invoke(UpdateGroupMembers $command): void
    {
        $this->em->wrapInTransaction(function () use ($command): void {
            $troop = $this->queryBus->handle(new TroopByIdQuery($command->getTroopId()));

            if ($command->getType() === 'patrol') {
                if ($command->getPatrolId() !== null) {
                    $patrol = $this->queryBus->handle(new PatrolByIdQuery($command->getPatrolId()));
                } else {
                    $patrolNumber = $troop->getPatrols()->count() + 1;
                    $patrol       = new Patrol($troop, $troop->getName() . '-' . sprintf('%02d', $patrolNumber));
                    $this->patrolRepository->save($patrol);
                }
            }

            foreach ($command->getPersons() as $person) {
                $personId = $person['personId'];
                $roleId   = $person['roleId'];

                $personDetail = $this->skautIsService->getPersonDetail($personId);

                $user = $this->userRepository->findBySkautISPersonId($personId);
                if ($user == null) {
                    $user = new User();
                    $user->setSkautISPersonId($personId);
                }

                $user->setFirstName($personDetail->FirstName);
                $user->setLastName($personDetail->LastName);
                $user->setNickName($personDetail->NickName);

                $user->setMember($personDetail->HasMembership);

                $birthdate = new DateTimeImmutable($personDetail->Birthday);
                $user->setBirthdate($birthdate);
                $user->setSex($personDetail->ID_Sex);

                if (property_exists($personDetail, 'Phone')) {
                    $user->setPhone($personDetail->Phone);
                }

                if (property_exists($personDetail, 'Email')) {
                    $user->setEmail($personDetail->Email);
                }

                $user->setStreet($personDetail->Street);
                $user->setCity($personDetail->City);
                $user->setPostcode($personDetail->Postcode);
                $user->setState($personDetail->State);

                if ((new DateTimeImmutable())->diff($birthdate)->y < 18) {
                    $personContacts = $this->skautIsService->getPersonContactAllParent($personId);

                    foreach ($personContacts as $contact) {
                        if ($contact->ID_ContactType === 'telefon_hlavni') {
                            switch ($contact->ID_ParentType) {
                                case 'mother':
                                    $user->setMotherName($contact->PersonPersonParent);
                                    $user->setMotherPhone($contact->Value);
                                    break;
                                case 'father':
                                    $user->setFatherName($contact->PersonPersonParent);
                                    $user->setFatherPhone($contact->Value);
                                    break;
                            }
                        }
                    }
                }

                $this->userRepository->save($user);

                $role = $this->roleRepository->findById($roleId);

                // todo odstranit nezvolene

                if ($command->getType() === 'patrol') {
                    $userGroupRoles = $this->userGroupRoleRepository->findByUserAndPatrol($user->getId(), $patrol->getId());
                    if ($userGroupRoles->isEmpty()) {
                        $userGroupRole = new UserGroupRole($user, $role, $patrol);
                    } else {
                        $userGroupRole = $userGroupRoles[0];
                        $userGroupRole->setRole($role);
                    }

                    $this->userGroupRoleRepository->save($userGroupRole);
                } elseif ($command->getType() === 'troop') {
                    $userGroupRoles = $this->userGroupRoleRepository->findByUserAndTroop($user->getId(), $troop->getId());
                    if ($userGroupRoles->isEmpty()) {
                        $userGroupRole = new UserGroupRole($user, $role, null, $troop);
                    } else {
                        $userGroupRole = $userGroupRoles[0];
                        $userGroupRole->setRole($role);
                    }

                    $this->userGroupRoleRepository->save($userGroupRole);
                }
            }
        });
    }
}
