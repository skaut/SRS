<?php

declare(strict_types=1);

namespace App\Model\User\Commands\Handlers;

use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Acl\Role;
use App\Model\Application\ApplicationFactory;
use App\Model\Application\Repositories\ApplicationRepository;
use App\Model\Structure\Repositories\SubeventRepository;
use App\Model\Structure\Subevent;
use App\Model\User\Commands\CheckTicket;
use App\Model\User\Repositories\TicketCheckRepository;
use App\Model\User\Repositories\UserRepository;
use App\Model\User\TicketCheck;
use App\Model\User\User;
use CommandHandlerTest;
use DateTimeImmutable;

final class CheckTicketHandlerTest extends CommandHandlerTest
{
    private TicketCheckRepository $ticketCheckRepository;

    private UserRepository $userRepository;

    private RoleRepository $roleRepository;

    private SubeventRepository $subeventRepository;

    private ApplicationRepository $applicationRepository;

    /**
     * Test vytvoření kontroly vstupenky a nastavení uživatele jako přítomného.
     */
    public function testCheckTicket(): void
    {
        $role = new Role('role');
        $this->roleRepository->save($role);

        $subevent = new Subevent();
        $subevent->setName('subevent');
        $this->subeventRepository->save($subevent);

        $user = new User();
        $user->setFirstName('First');
        $user->setLastName('Last');
        $user->addRole($role);
        $user->setApproved(true);
        $this->userRepository->save($user);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user, $role);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user, $subevent);

        $this->assertFalse($user->isAttended());

        $this->commandBus->handle(new CheckTicket($user, $subevent));

        $ticketChecks = $this->ticketCheckRepository->findByUserAndSubevent($user, $subevent);
        $this->assertEquals(1, $ticketChecks->count());
        $this->assertEquals($user, $ticketChecks->get(0)->getUser());
        $this->assertEquals($subevent, $ticketChecks->get(0)->getSubevent());
        $this->assertLessThanOrEqual(new DateTimeImmutable(), $ticketChecks->get(0)->getDatetime());
        $this->assertTrue($user->isAttended());
    }

    /** @return string[] */
    protected function getTestedAggregateRoots(): array
    {
        return [TicketCheck::class];
    }

    protected function _before(): void
    {
        $this->getModule('IntegrationTester')->useConfigFiles([__DIR__ . '/CheckTicketHandlerTest.neon']);

        parent::_before();

        $this->ticketCheckRepository = $this->getModule('IntegrationTester')->grabService(TicketCheckRepository::class);
        $this->userRepository        = $this->getModule('IntegrationTester')->grabService(UserRepository::class);
        $this->roleRepository        = $this->getModule('IntegrationTester')->grabService(RoleRepository::class);
        $this->subeventRepository    = $this->getModule('IntegrationTester')->grabService(SubeventRepository::class);
        $this->applicationRepository = $this->getModule('IntegrationTester')->grabService(ApplicationRepository::class);
    }
}
