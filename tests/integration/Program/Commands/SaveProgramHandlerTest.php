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
use App\Model\Program\Repositories\ProgramRepository;
use App\Model\Program\Repositories\RoomRepository;
use App\Model\Program\Room;
use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Settings\Repositories\SettingsRepository;
use App\Model\Settings\Settings;
use App\Model\Structure\Repositories\SubeventRepository;
use App\Model\Structure\Subevent;
use App\Model\User\Repositories\UserRepository;
use App\Model\User\User;
use CommandHandlerTest;
use DateTimeImmutable;
use Throwable;

final class SaveProgramHandlerTest extends CommandHandlerTest
{
    private BlockRepository $blockRepository;

    private SubeventRepository $subeventRepository;

    private UserRepository $userRepository;

    private RoleRepository $roleRepository;

    private ApplicationRepository $applicationRepository;

    private ProgramApplicationRepository $programApplicationRepository;

    private RoomRepository $roomRepository;

    private ProgramRepository $programRepository;

    private SettingsRepository $settingsRepository;

    /**
     * Vytvoření volitelného programu.
     *
     * @skip temporary skip because of Translator
     */
    public function testCreateVoluntaryProgram(): void
    {
        $subevent = new Subevent();
        $subevent->setName('subevent');
        $this->subeventRepository->save($subevent);

        $block = new Block('block', 60, 10, true, ProgramMandatoryType::VOLUNTARY);
        $block->setSubevent($subevent);
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

        $program = new Program(new DateTimeImmutable('2020-01-01 08:00'));
        $program->setBlock($block);
        $this->commandBus->handle(new SaveProgram($program));

        $this->assertNull($this->programApplicationRepository->findByUserAndProgram($user1, $program));
        $this->assertNull($this->programApplicationRepository->findByUserAndProgram($user2, $program));
        $this->assertEquals(0, $program->getAttendeesCount());
    }

    /**
     * Vytvoření automaticky zapisovaného programu - oprávnění uživatelé jsou zapsáni.
     *
     * @throws SettingsItemNotFoundException
     *
     * @skip temporary skip because of Translator
     */
    public function testCreateAutoRegisteredProgram(): void
    {
        $subevent = new Subevent();
        $subevent->setName('subevent');
        $this->subeventRepository->save($subevent);

        $block = new Block('block', 60, null, true, ProgramMandatoryType::AUTO_REGISTERED);
        $block->setSubevent($subevent);
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

        $setting = $this->settingsRepository->findByItem(Settings::IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT);
        $setting->setValue((string) false);
        $this->settingsRepository->save($setting);

        $program = new Program(new DateTimeImmutable('2020-01-01 08:00'));
        $program->setBlock($block);
        $this->commandBus->handle(new SaveProgram($program));

        $this->assertNotNull($this->programApplicationRepository->findByUserAndProgram($user1, $program));
        $this->assertNotNull($this->programApplicationRepository->findByUserAndProgram($user2, $program));
        $this->assertNull($this->programApplicationRepository->findByUserAndProgram($user3, $program));
        $this->assertEquals(2, $program->getAttendeesCount());
    }

    /**
     * Vytvoření automaticky zapisovaného programu - oprávnění uživatelé jsou zapsáni, včetně nezaplacených.
     *
     * @throws SettingsItemNotFoundException
     * @throws Throwable
     *
     * @skip temporary skip because of Translator
     */
    public function testCreateAutoRegisteredProgramNotPaidAllowed(): void
    {
        $subevent = new Subevent();
        $subevent->setName('subevent');
        $this->subeventRepository->save($subevent);

        $block = new Block('block', 60, null, true, ProgramMandatoryType::AUTO_REGISTERED);
        $block->setSubevent($subevent);
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

        $setting = $this->settingsRepository->findByItem(Settings::IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT);
        $setting->setValue((string) true);
        $this->settingsRepository->save($setting);

        $program = new Program(new DateTimeImmutable('2020-01-01 08:00'));
        $program->setBlock($block);
        $this->commandBus->handle(new SaveProgram($program));

        $this->assertNotNull($this->programApplicationRepository->findByUserAndProgram($user1, $program));
        $this->assertNotNull($this->programApplicationRepository->findByUserAndProgram($user2, $program));
        $this->assertNotNull($this->programApplicationRepository->findByUserAndProgram($user3, $program));
        $this->assertEquals(3, $program->getAttendeesCount());
    }

    /**
     * Test uložení změn programu.
     *
     * @skip temporary skip because of Translator
     */
    public function testUpdateProgram(): void
    {
        $subevent = new Subevent();
        $subevent->setName('subevent');
        $this->subeventRepository->save($subevent);

        $block = new Block('block', 60, null, true, ProgramMandatoryType::AUTO_REGISTERED);
        $block->setSubevent($subevent);
        $this->blockRepository->save($block);

        $program = new Program(new DateTimeImmutable('2020-01-01 08:00'));
        $program->setBlock($block);
        $this->commandBus->handle(new SaveProgram($program));

        $room = new Room('room', null);
        $this->roomRepository->save($room);

        $program->setStart(new DateTimeImmutable('2020-01-01 09:00'));
        $program->setRoom($room);

        $this->commandBus->handle(new SaveProgram($program));

        $program = $this->programRepository->findById($program->getId());

        $this->assertEquals(new DateTimeImmutable('2020-01-01 09:00'), $program->getStart());
        $this->assertEquals($room, $program->getRoom());
    }

    /** @return string[] */
    protected function getTestedAggregateRoots(): array
    {
        return [Program::class, Settings::class];
    }

    protected function _before(): void
    {
        $this->tester->useConfigFiles([__DIR__ . '/SaveProgramHandlerTest.neon']);

        parent::_before();

        $this->blockRepository              = $this->tester->grabService(BlockRepository::class);
        $this->subeventRepository           = $this->tester->grabService(SubeventRepository::class);
        $this->userRepository               = $this->tester->grabService(UserRepository::class);
        $this->roleRepository               = $this->tester->grabService(RoleRepository::class);
        $this->applicationRepository        = $this->tester->grabService(ApplicationRepository::class);
        $this->programApplicationRepository = $this->tester->grabService(ProgramApplicationRepository::class);
        $this->roomRepository               = $this->tester->grabService(RoomRepository::class);
        $this->programRepository            = $this->tester->grabService(ProgramRepository::class);
        $this->settingsRepository           = $this->tester->grabService(SettingsRepository::class);

        $this->settingsRepository->save(new Settings(Settings::IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT, (string) false));
        $this->settingsRepository->save(new Settings(Settings::SEMINAR_NAME, 'test'));
    }
}
