<?php

declare(strict_types=1);

namespace App\Model\Structure\Repositories;

use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Acl\Role;
use App\Model\Application\ApplicationFactory;
use App\Model\Application\Repositories\ApplicationRepository;
use App\Model\Enums\ApplicationState;
use App\Model\Settings\Settings;
use App\Model\Structure\Subevent;
use App\Model\User\Repositories\UserRepository;
use App\Model\User\User;
use DateTimeImmutable;
use IntegrationTest;

final class SubeventRepositoryTest extends IntegrationTest
{
    private SubeventRepository $subeventRepository;

    private UserRepository $userRepository;

    private RoleRepository $roleRepository;

    private ApplicationRepository $applicationRepository;

    private Subevent $subevent;

    private Role $role;

    /**
     * Počítají se jen platné nezrušené přihlášky.
     */
    public function testCountUsersInSubevent(): void
    {
        $this->createApplication(ApplicationState::PAID);
        $this->createApplication(ApplicationState::PAID_FREE);
        $this->createApplication(ApplicationState::PAID_TRANSFERED);
        $this->createApplication(ApplicationState::WAITING_FOR_PAYMENT);

        $this->createApplication(ApplicationState::CANCELED);
        $this->createApplication(ApplicationState::CANCELED_NOT_PAID);
        $this->createApplication(ApplicationState::CANCELED_TRANSFERED);

        // historizovaná (nahrazená novější verzí) přihláška se nepočítá
        $this->createApplication(ApplicationState::PAID, new DateTimeImmutable());

        $this->assertSame(4, $this->subeventRepository->countUsersInSubevent($this->subevent));
    }

    /**
     * Počet nezávisí na denormalizovaném sloupci occupancy.
     *
     * Ten se v existujících instalacích rozešel se skutečností, kontroly kapacit z něj
     * proto nesmí vycházet.
     */
    public function testCountUsersIgnoresOccupancyColumn(): void
    {
        $this->createApplication(ApplicationState::PAID);

        $this->subeventRepository->incrementOccupancy($this->subevent);
        $this->subeventRepository->incrementOccupancy($this->subevent);
        $this->subeventRepository->incrementOccupancy($this->subevent);

        $this->assertSame(1, $this->subeventRepository->countUsersInSubevent($this->subevent));
    }

    /**
     * Podakce bez přihlášek má nulu, ne chybějící klíč.
     */
    public function testCountUsersInSubeventsWithoutApplications(): void
    {
        $this->assertSame(
            [$this->subevent->getId() => 0],
            $this->subeventRepository->countUsersInSubevents([$this->subevent->getId()]),
        );

        $this->assertSame([], $this->subeventRepository->countUsersInSubevents([]));
    }

    /** @return string[] */
    protected function getTestedAggregateRoots(): array
    {
        return [User::class, Settings::class];
    }

    protected function _before(): void
    {
        $this->tester->useConfigFiles([__DIR__ . '/SubeventRepositoryTest.neon']);

        parent::_before();

        $this->subeventRepository    = $this->tester->grabService(SubeventRepository::class);
        $this->userRepository        = $this->tester->grabService(UserRepository::class);
        $this->roleRepository        = $this->tester->grabService(RoleRepository::class);
        $this->applicationRepository = $this->tester->grabService(ApplicationRepository::class);

        $this->subevent = new Subevent();
        $this->subevent->setName('subevent');
        $this->subeventRepository->save($this->subevent);

        $this->role = new Role('role');
        $this->roleRepository->save($this->role);
    }

    private function createApplication(string $state, DateTimeImmutable|null $validTo = null): void
    {
        $user = new User();
        $user->setFirstName('First');
        $user->setLastName('Last');
        $user->addRole($this->role);
        $user->setApproved(true);
        $this->userRepository->save($user);

        $application = ApplicationFactory::createSubeventsApplication(
            $this->applicationRepository,
            $user,
            $this->subevent,
            $state,
        );

        if ($validTo === null) {
            return;
        }

        $application->setValidTo($validTo);
        $this->applicationRepository->save($application);
    }
}
