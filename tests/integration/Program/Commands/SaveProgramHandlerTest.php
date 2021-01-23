<?php

declare(strict_types=1);

namespace App\Model\Program\Commands\Handlers;

use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Acl\Role;
use App\Model\Application\ApplicationFactory;
use App\Model\Application\Repositories\ApplicationRepository;
use App\Model\Enums\ApplicationState;
use App\Model\Enums\ProgramMandatoryType;
use App\Model\Program\Block;
use App\Model\Program\Commands\SaveProgram;
use App\Model\Program\Program;
use App\Model\Program\Repositories\BlockRepository;
use App\Model\Program\Repositories\ProgramApplicationRepository;
use App\Model\Settings\Exceptions\SettingsException;
use App\Model\Settings\Settings;
use App\Model\Structure\Repositories\SubeventRepository;
use App\Model\Structure\Subevent;
use App\Model\User\Repositories\UserRepository;
use App\Model\User\User;
use App\Services\ISettingsService;
use CommandHandlerTest;
use DateTimeImmutable;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Throwable;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNotNull;
use function PHPUnit\Framework\assertNull;

final class SaveProgramHandlerTest extends CommandHandlerTest
{
    private ISettingsService $settingsService;

    private BlockRepository $blockRepository;

    private SubeventRepository $subeventRepository;

    private UserRepository $userRepository;

    private RoleRepository $roleRepository;

    private ApplicationRepository $applicationRepository;

    private ProgramApplicationRepository $programApplicationRepository;

    /**
     * Vytvoření volitelného programu.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testVoluntaryProgram(): void
    {
        $subevent = new Subevent();
        $subevent->setName('subevent');
        $this->subeventRepository->save($subevent);

        $block = new Block('block', 60, 10, true, ProgramMandatoryType::VOLUNTARY, $subevent, null);
        $this->blockRepository->save($block);

        $role = new Role('role');
        $this->roleRepository->save($role);

        $user1 = new User();
        $user1->setFirstName('First');
        $user1->setLastName('Last');
        $user1->addRole($role);
        $user1->setApproved(true);
        $this->userRepository->save($user1);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user1, $role);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user1, $subevent);

        $user2 = new User();
        $user2->setFirstName('First');
        $user2->setLastName('Last');
        $user2->addRole($role);
        $user2->setApproved(true);
        $this->userRepository->save($user2);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user2, $role);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user2, $subevent);

        $program = new Program($block, null, new DateTimeImmutable('2020-01-01 08:00'));
        $this->commandBus->handle(new SaveProgram($program));

        assertNull($this->programApplicationRepository->findUserProgramApplication($user1, $program));
        assertNull($this->programApplicationRepository->findUserProgramApplication($user2, $program));
        assertEquals(0, $program->getOccupancy());
    }

    /**
     * Vytvoření automaticky zapisovaného programu - oprávnění uživatelé jsou zapsáni.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testAutoRegisteredProgram(): void
    {
        $subevent = new Subevent();
        $subevent->setName('subevent');
        $this->subeventRepository->save($subevent);

        $block = new Block('block', 60, null, true, ProgramMandatoryType::AUTO_REGISTERED, $subevent, null);
        $this->blockRepository->save($block);

        $role = new Role('role');
        $this->roleRepository->save($role);

        $user1 = new User();
        $user1->setFirstName('First');
        $user1->setLastName('Last');
        $user1->addRole($role);
        $user1->setApproved(true);
        $this->userRepository->save($user1);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user1, $role);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user1, $subevent);

        $user2 = new User();
        $user2->setFirstName('First');
        $user2->setLastName('Last');
        $user2->addRole($role);
        $user2->setApproved(true);
        $this->userRepository->save($user2);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user2, $role);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user2, $subevent);

        $user3 = new User();
        $user3->setFirstName('First');
        $user3->setLastName('Last');
        $user3->addRole($role);
        $user3->setApproved(true);
        $this->userRepository->save($user3);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user3, $role);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user3, $subevent, ApplicationState::WAITING_FOR_PAYMENT);

        $program = new Program($block, null, new DateTimeImmutable('2020-01-01 08:00'));
        $this->commandBus->handle(new SaveProgram($program));

        assertNotNull($this->programApplicationRepository->findUserProgramApplication($user1, $program));
        assertNotNull($this->programApplicationRepository->findUserProgramApplication($user2, $program));
        assertNull($this->programApplicationRepository->findUserProgramApplication($user3, $program));
        assertEquals(2, $program->getOccupancy());
    }

    /**
     * Vytvoření automaticky zapisovaného programu - oprávnění uživatelé jsou zapsáni, včetně nezaplacených.
     *
     * @throws SettingsException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Throwable
     */
    public function testAutoRegisteredProgramNotPaidAllowed(): void
    {
        $subevent = new Subevent();
        $subevent->setName('subevent');
        $this->subeventRepository->save($subevent);

        $block = new Block('block', 60, null, true, ProgramMandatoryType::AUTO_REGISTERED, $subevent, null);
        $this->blockRepository->save($block);

        $role = new Role('role');
        $this->roleRepository->save($role);

        $user1 = new User();
        $user1->setFirstName('First');
        $user1->setLastName('Last');
        $user1->addRole($role);
        $user1->setApproved(true);
        $this->userRepository->save($user1);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user1, $role);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user1, $subevent);

        $user2 = new User();
        $user2->setFirstName('First');
        $user2->setLastName('Last');
        $user2->addRole($role);
        $user2->setApproved(true);
        $this->userRepository->save($user2);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user2, $role);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user2, $subevent);

        $user3 = new User();
        $user3->setFirstName('First');
        $user3->setLastName('Last');
        $user3->addRole($role);
        $user3->setApproved(true);
        $this->userRepository->save($user3);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user3, $role);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user3, $subevent, ApplicationState::WAITING_FOR_PAYMENT);

        $this->settingsService->setBoolValue(Settings::IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT, true);

        $program = new Program($block, null, new DateTimeImmutable('2020-01-01 08:00'));
        $this->commandBus->handle(new SaveProgram($program));

        assertNotNull($this->programApplicationRepository->findUserProgramApplication($user1, $program));
        assertNotNull($this->programApplicationRepository->findUserProgramApplication($user2, $program));
        assertNotNull($this->programApplicationRepository->findUserProgramApplication($user3, $program));
        assertEquals(3, $program->getOccupancy());
    }

    /**
     * @return string[]
     */
    protected function getTestedAggregateRoots(): array
    {
        return [Program::class];
    }

    protected function _before(): void
    {
        $this->tester->useConfigFiles([__DIR__ . '/SaveProgramHandlerTest.neon']);
        parent::_before();

        $this->settingsService              = $this->tester->grabService(ISettingsService::class);
        $this->blockRepository              = $this->tester->grabService(BlockRepository::class);
        $this->subeventRepository           = $this->tester->grabService(SubeventRepository::class);
        $this->userRepository               = $this->tester->grabService(UserRepository::class);
        $this->roleRepository               = $this->tester->grabService(RoleRepository::class);
        $this->applicationRepository        = $this->tester->grabService(ApplicationRepository::class);
        $this->programApplicationRepository = $this->tester->grabService(ProgramApplicationRepository::class);

        $this->settingsService->setBoolValue(Settings::IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT, false);
        $this->settingsService->setValue(Settings::SEMINAR_NAME, 'test');
    }
}
