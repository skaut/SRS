<?php

declare(strict_types=1);

namespace App\Model\User\Commands\Handlers;

use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Acl\Role;
use App\Model\Application\ApplicationFactory;
use App\Model\Application\Repositories\ApplicationRepository;
use App\Model\Enums\ApplicationState;
use App\Model\Enums\ProgramMandatoryType;
use App\Model\Program\Block;
use App\Model\Program\Category;
use App\Model\Program\Exceptions\ProgramCapacityOccupiedException;
use App\Model\Program\Exceptions\UserAlreadyAttendsBlockException;
use App\Model\Program\Exceptions\UserAlreadyAttendsProgramException;
use App\Model\Program\Exceptions\UserAttendsConflictingProgramException;
use App\Model\Program\Exceptions\UserNotAllowedProgramException;
use App\Model\Program\Program;
use App\Model\Program\Repositories\BlockRepository;
use App\Model\Program\Repositories\CategoryRepository;
use App\Model\Program\Repositories\ProgramApplicationRepository;
use App\Model\Program\Repositories\ProgramRepository;
use App\Model\Settings\Settings;
use App\Model\Structure\Repositories\SubeventRepository;
use App\Model\Structure\Subevent;
use App\Model\User\Commands\RegisterProgram;
use App\Model\User\Repositories\UserRepository;
use App\Model\User\User;
use App\Services\ISettingsService;
use CommandHandlerTest;
use DateTimeImmutable;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

final class RegisterProgramHandlerTest extends CommandHandlerTest
{
    private ISettingsService $settingsService;

    private BlockRepository $blockRepository;

    private SubeventRepository $subeventRepository;

    private UserRepository $userRepository;

    private CategoryRepository $categoryRepository;

    private RoleRepository $roleRepository;

    private ProgramRepository $programRepository;

    private ApplicationRepository $applicationRepository;

    private ProgramApplicationRepository $programApplicationRepository;

    /**
     * 1. uživatel se na program registruje jako poslední, 2. se stává náhradník.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testAlternatesAllowed(): void
    {
        $subevent = new Subevent();
        $subevent->setName('subevent');
        $this->subeventRepository->save($subevent);

        $block = new Block('block', 60, 1, true, ProgramMandatoryType::VOLUNTARY);
        $block->setSubevent($subevent);
        $this->blockRepository->save($block);

        $program = new Program(new DateTimeImmutable('2020-01-01 08:00'));
        $program->setBlock($block);
        $this->programRepository->save($program);

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

        $this->commandBus->handle(new RegisterProgram($user1, $program, false));
        $programApplication1 = $this->programApplicationRepository->findByUserAndProgram($user1, $program);
        $this->assertEquals($user1, $programApplication1->getUser());
        $this->assertEquals($program, $programApplication1->getProgram());
        $this->assertFalse($programApplication1->isAlternate());
        $this->assertEquals(1, $program->getAttendeesCount());
        $this->assertEquals(0, $program->getAlternatesCount());

        $this->commandBus->handle(new RegisterProgram($user2, $program, false));
        $programApplication2 = $this->programApplicationRepository->findByUserAndProgram($user2, $program);
        $this->assertEquals($user2, $programApplication2->getUser());
        $this->assertEquals($program, $programApplication2->getProgram());
        $this->assertTrue($programApplication2->isAlternate());
        $this->assertEquals(1, $program->getAttendeesCount());
        $this->assertEquals(1, $program->getAlternatesCount());
    }

    /**
     * 1. uživatel se na program registruje jako poslední, 2. se registrovat nemůže (náhradníci nejsou povoleni).
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testAlterantesNotAllowed(): void
    {
        $subevent = new Subevent();
        $subevent->setName('subevent');
        $this->subeventRepository->save($subevent);

        $block = new Block('block', 60, 1, false, ProgramMandatoryType::VOLUNTARY);
        $block->setSubevent($subevent);
        $this->blockRepository->save($block);

        $program = new Program(new DateTimeImmutable('2020-01-01 08:00'));
        $program->setBlock($block);
        $this->programRepository->save($program);

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

        $this->commandBus->handle(new RegisterProgram($user1, $program, false));
        $programApplication1 = $this->programApplicationRepository->findByUserAndProgram($user1, $program);
        $this->assertEquals($user1, $programApplication1->getUser());
        $this->assertEquals($program, $programApplication1->getProgram());
        $this->assertFalse($programApplication1->isAlternate());

        $this->expectException(ProgramCapacityOccupiedException::class);
        try {
            $this->commandBus->handle(new RegisterProgram($user2, $program, false));
        } catch (HandlerFailedException $e) {
            throw $e->getPrevious();
        }
    }

    /**
     * Role uživatele není mezi povolenými pro kategorii.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testWrongRole(): void
    {
        $subevent = new Subevent();
        $subevent->setName('subevent');
        $this->subeventRepository->save($subevent);

        $category = new Category('category');
        $this->categoryRepository->save($category);

        $block = new Block('block', 60, null, false, ProgramMandatoryType::VOLUNTARY);
        $block->setSubevent($subevent);
        $block->setCategory($category);
        $this->blockRepository->save($block);

        $program = new Program(new DateTimeImmutable('2020-01-01 08:00'));
        $program->setBlock($block);
        $this->programRepository->save($program);

        $role = new Role('role');
        $this->roleRepository->save($role);

        $user = new User();
        $user->setFirstName('First');
        $user->setLastName('Last');
        $user->addRole($role);
        $user->setApproved(true);
        $this->userRepository->save($user);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user, $role);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user, $subevent);

        $this->expectException(UserNotAllowedProgramException::class);
        try {
            $this->commandBus->handle(new RegisterProgram($user, $program, false));
        } catch (HandlerFailedException $e) {
            throw $e->getPrevious();
        }
    }

    /**
     * Uživatel není na akci přihlášený (chybějící role, chybějící přihláška podakcí).
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testNotRegistered(): void
    {
        $subevent = new Subevent();
        $subevent->setName('subevent');
        $this->subeventRepository->save($subevent);

        $block = new Block('block', 60, null, false, ProgramMandatoryType::VOLUNTARY);
        $block->setSubevent($subevent);
        $this->blockRepository->save($block);

        $program = new Program(new DateTimeImmutable('2020-01-01 08:00'));
        $program->setBlock($block);
        $this->programRepository->save($program);

        $user = new User();
        $user->setFirstName('First');
        $user->setLastName('Last');
        $user->setApproved(true);
        $this->userRepository->save($user);

        $this->expectException(UserNotAllowedProgramException::class);
        try {
            $this->commandBus->handle(new RegisterProgram($user, $program, false));
        } catch (HandlerFailedException $e) {
            throw $e->getPrevious();
        }
    }

    /**
     * Uživatel není schválený.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testNotApproved(): void
    {
        $subevent = new Subevent();
        $subevent->setName('subevent');
        $this->subeventRepository->save($subevent);

        $block = new Block('block', 60, null, false, ProgramMandatoryType::VOLUNTARY);
        $block->setSubevent($subevent);
        $this->blockRepository->save($block);

        $program = new Program(new DateTimeImmutable('2020-01-01 08:00'));
        $program->setBlock($block);
        $this->programRepository->save($program);

        $role = new Role('role');
        $this->roleRepository->save($role);

        $user = new User();
        $user->setFirstName('First');
        $user->setLastName('Last');
        $user->addRole($role);
        $user->setApproved(false);
        $this->userRepository->save($user);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user, $role);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user, $subevent);

        $this->expectException(UserNotAllowedProgramException::class);
        try {
            $this->commandBus->handle(new RegisterProgram($user, $program, false));
        } catch (HandlerFailedException $e) {
            throw $e->getPrevious();
        }
    }

    /**
     * Uživatel nemá zaplacenou registraci a není povoleno přihlašování před zaplacením.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testNotPaid(): void
    {
        $subevent = new Subevent();
        $subevent->setName('subevent');
        $this->subeventRepository->save($subevent);

        $block = new Block('block', 60, null, false, ProgramMandatoryType::VOLUNTARY);
        $block->setSubevent($subevent);
        $this->blockRepository->save($block);

        $program = new Program(new DateTimeImmutable('2020-01-01 08:00'));
        $program->setBlock($block);
        $this->programRepository->save($program);

        $role = new Role('role');
        $this->roleRepository->save($role);

        $user = new User();
        $user->setFirstName('First');
        $user->setLastName('Last');
        $user->addRole($role);
        $user->setApproved(true);
        $this->userRepository->save($user);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user, $role, ApplicationState::WAITING_FOR_PAYMENT);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user, $subevent);

        $this->expectException(UserNotAllowedProgramException::class);
        try {
            $this->commandBus->handle(new RegisterProgram($user, $program, false));
        } catch (HandlerFailedException $e) {
            throw $e->getPrevious();
        }
    }

    /**
     * Uživatel není přihlášen na správnou podakci.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testWrongSubevent(): void
    {
        $subevent = new Subevent();
        $subevent->setName('subevent');
        $this->subeventRepository->save($subevent);

        $block = new Block('block', 60, null, false, ProgramMandatoryType::VOLUNTARY);
        $block->setSubevent($subevent);
        $this->blockRepository->save($block);

        $program = new Program(new DateTimeImmutable('2020-01-01 08:00'));
        $program->setBlock($block);
        $this->programRepository->save($program);

        $role = new Role('role');
        $this->roleRepository->save($role);

        $user = new User();
        $user->setFirstName('First');
        $user->setLastName('Last');
        $user->addRole($role);
        $user->setApproved(true);
        $this->userRepository->save($user);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user, $role);

        $this->expectException(UserNotAllowedProgramException::class);
        try {
            $this->commandBus->handle(new RegisterProgram($user, $program, false));
        } catch (HandlerFailedException $e) {
            throw $e->getPrevious();
        }
    }

    /**
     * Uživatel nemá příslušnou podakci zaplacenou a není povoleno přihlašování před zaplacením.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testNotPaidSubevent(): void
    {
        $subevent = new Subevent();
        $subevent->setName('subevent');
        $this->subeventRepository->save($subevent);

        $block = new Block('block', 60, null, false, ProgramMandatoryType::VOLUNTARY);
        $block->setSubevent($subevent);
        $this->blockRepository->save($block);

        $program = new Program(new DateTimeImmutable('2020-01-01 08:00'));
        $program->setBlock($block);
        $this->programRepository->save($program);

        $role = new Role('role');
        $this->roleRepository->save($role);

        $user = new User();
        $user->setFirstName('First');
        $user->setLastName('Last');
        $user->addRole($role);
        $user->setApproved(true);
        $this->userRepository->save($user);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user, $role);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user, $subevent, ApplicationState::WAITING_FOR_PAYMENT);

        $this->expectException(UserNotAllowedProgramException::class);
        try {
            $this->commandBus->handle(new RegisterProgram($user, $program, false));
        } catch (HandlerFailedException $e) {
            throw $e->getPrevious();
        }
    }

    /**
     * Uživatel nemá příslušnou podakci zaplacenou, ale je povoleno přihlašování před zaplacením.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testNotPaidSubeventAllowed(): void
    {
        $subevent = new Subevent();
        $subevent->setName('subevent');
        $this->subeventRepository->save($subevent);

        $block = new Block('block', 60, null, false, ProgramMandatoryType::VOLUNTARY);
        $block->setSubevent($subevent);
        $this->blockRepository->save($block);

        $program = new Program(new DateTimeImmutable('2020-01-01 08:00'));
        $program->setBlock($block);
        $this->programRepository->save($program);

        $role = new Role('role');
        $this->roleRepository->save($role);

        $user = new User();
        $user->setFirstName('First');
        $user->setLastName('Last');
        $user->addRole($role);
        $user->setApproved(true);
        $this->userRepository->save($user);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user, $role);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user, $subevent, ApplicationState::WAITING_FOR_PAYMENT);

        $this->settingsService->setBoolValue(Settings::IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT, true);

        $this->commandBus->handle(new RegisterProgram($user, $program, false));
        $programApplication = $this->programApplicationRepository->findByUserAndProgram($user, $program);
        $this->assertEquals($user, $programApplication->getUser());
        $this->assertEquals($program, $programApplication->getProgram());
    }

    /**
     * Uživatel už se stejného programu účastní.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testAlreadyAttendsProgram(): void
    {
        $subevent = new Subevent();
        $subevent->setName('subevent');
        $this->subeventRepository->save($subevent);

        $block = new Block('block', 60, 1, true, ProgramMandatoryType::VOLUNTARY);
        $block->setSubevent($subevent);
        $this->blockRepository->save($block);

        $program = new Program(new DateTimeImmutable('2020-01-01 08:00'));
        $program->setBlock($block);
        $this->programRepository->save($program);

        $role = new Role('role');
        $this->roleRepository->save($role);

        $user = new User();
        $user->setFirstName('First');
        $user->setLastName('Last');
        $user->addRole($role);
        $user->setApproved(true);
        $this->userRepository->save($user);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user, $role);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user, $subevent);

        $this->commandBus->handle(new RegisterProgram($user, $program, false));
        $programApplication1 = $this->programApplicationRepository->findByUserAndProgram($user, $program);
        $this->assertEquals($user, $programApplication1->getUser());
        $this->assertEquals($program, $programApplication1->getProgram());
        $this->assertFalse($programApplication1->isAlternate());

        $this->expectException(UserAlreadyAttendsProgramException::class);
        try {
            $this->commandBus->handle(new RegisterProgram($user, $program, false));
        } catch (HandlerFailedException $e) {
            throw $e->getPrevious();
        }
    }

    /**
     * Uživatel už se stejného programového bloku účastní.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testAlreadyAttendsBlock(): void
    {
        $subevent = new Subevent();
        $subevent->setName('subevent');
        $this->subeventRepository->save($subevent);

        $block = new Block('block', 60, 1, true, ProgramMandatoryType::VOLUNTARY);
        $block->setSubevent($subevent);
        $this->blockRepository->save($block);

        $program1 = new Program(new DateTimeImmutable('2020-01-01 08:00'));
        $program1->setBlock($block);
        $this->programRepository->save($program1);

        $program2 = new Program(new DateTimeImmutable('2020-01-01 10:00'));
        $program2->setBlock($block);
        $this->programRepository->save($program2);

        $role = new Role('role');
        $this->roleRepository->save($role);

        $user = new User();
        $user->setFirstName('First');
        $user->setLastName('Last');
        $user->addRole($role);
        $user->setApproved(true);
        $this->userRepository->save($user);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user, $role);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user, $subevent);

        $this->commandBus->handle(new RegisterProgram($user, $program1, false));
        $programApplication1 = $this->programApplicationRepository->findByUserAndProgram($user, $program1);
        $this->assertEquals($user, $programApplication1->getUser());
        $this->assertEquals($program1, $programApplication1->getProgram());
        $this->assertFalse($programApplication1->isAlternate());

        $this->expectException(UserAlreadyAttendsBlockException::class);
        try {
            $this->commandBus->handle(new RegisterProgram($user, $program2, false));
        } catch (HandlerFailedException $e) {
            throw $e->getPrevious();
        }
    }

    /**
     * Uživatel má zapsaný program, který se časově překrývá.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testAttendsConflictingProgram(): void
    {
        $subevent = new Subevent();
        $subevent->setName('subevent');
        $this->subeventRepository->save($subevent);

        $block1 = new Block('block-1', 60, 1, true, ProgramMandatoryType::VOLUNTARY);
        $block1->setSubevent($subevent);
        $this->blockRepository->save($block1);

        $program1 = new Program(new DateTimeImmutable('2020-01-01 08:00'));
        $program1->setBlock($block1);
        $this->programRepository->save($program1);

        $block2 = new Block('block-2', 60, 1, true, ProgramMandatoryType::VOLUNTARY);
        $block2->setSubevent($subevent);
        $this->blockRepository->save($block2);

        $program2 = new Program(new DateTimeImmutable('2020-01-01 09:00'));
        $program2->setBlock($block2);
        $this->programRepository->save($program2);

        $block3 = new Block('block-3', 60, 1, true, ProgramMandatoryType::VOLUNTARY);
        $block3->setSubevent($subevent);
        $this->blockRepository->save($block3);

        $program3 = new Program(new DateTimeImmutable('2020-01-01 09:30'));
        $program3->setBlock($block3);
        $this->programRepository->save($program3);

        $role = new Role('role');
        $this->roleRepository->save($role);

        $user = new User();
        $user->setFirstName('First');
        $user->setLastName('Last');
        $user->addRole($role);
        $user->setApproved(true);
        $this->userRepository->save($user);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user, $role);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user, $subevent);

        $this->commandBus->handle(new RegisterProgram($user, $program1, false));
        $programApplication1 = $this->programApplicationRepository->findByUserAndProgram($user, $program1);
        $this->assertEquals($user, $programApplication1->getUser());
        $this->assertEquals($program1, $programApplication1->getProgram());
        $this->assertFalse($programApplication1->isAlternate());

        $this->commandBus->handle(new RegisterProgram($user, $program2, false));
        $programApplication2 = $this->programApplicationRepository->findByUserAndProgram($user, $program2);
        $this->assertEquals($user, $programApplication2->getUser());
        $this->assertEquals($program2, $programApplication2->getProgram());
        $this->assertFalse($programApplication2->isAlternate());

        $this->expectException(UserAttendsConflictingProgramException::class);
        try {
            $this->commandBus->handle(new RegisterProgram($user, $program3, false));
        } catch (HandlerFailedException $e) {
            throw $e->getPrevious();
        }
    }

    /**
     * @return string[]
     */
    protected function getTestedAggregateRoots(): array
    {
        return [User::class];
    }

    protected function _before(): void
    {
        $this->tester->useConfigFiles([__DIR__ . '/RegisterProgramHandlerTest.neon']);
        parent::_before();

        $this->settingsService              = $this->tester->grabService(ISettingsService::class);
        $this->blockRepository              = $this->tester->grabService(BlockRepository::class);
        $this->subeventRepository           = $this->tester->grabService(SubeventRepository::class);
        $this->userRepository               = $this->tester->grabService(UserRepository::class);
        $this->categoryRepository           = $this->tester->grabService(CategoryRepository::class);
        $this->roleRepository               = $this->tester->grabService(RoleRepository::class);
        $this->programRepository            = $this->tester->grabService(ProgramRepository::class);
        $this->applicationRepository        = $this->tester->grabService(ApplicationRepository::class);
        $this->programApplicationRepository = $this->tester->grabService(ProgramApplicationRepository::class);

        $this->settingsService->setBoolValue(Settings::IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT, false);
    }
}
