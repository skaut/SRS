<?php

declare(strict_types=1);

namespace App\Model\User\Commands\Handlers;

use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Acl\Role;
use App\Model\Application\Repositories\ApplicationRepository;
use App\Model\Application\SubeventsApplication;
use App\Model\Enums\ApplicationState;
use App\Model\Enums\ProgramMandatoryType;
use App\Model\Program\Block;
use App\Model\Program\Category;
use App\Model\Program\Exceptions\ProgramCapacityOccupiedException;
use App\Model\Program\Exceptions\UserAlreadyAttendsBlockException;
use App\Model\Program\Exceptions\UserAlreadyAttendsProgramException;
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
use Doctrine\Common\Collections\ArrayCollection;
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

    public function testAlterantesAllowed(): void
    {
        $subevent = new Subevent();
        $subevent->setName("subevent");
        $this->subeventRepository->save($subevent);

        $block = new Block("block", 60, 1, true, ProgramMandatoryType::VOLUNTARY, $subevent, null);
        $this->blockRepository->save($block);

        $program = new Program($block, null, new DateTimeImmutable('2020-01-01 08:00'));
        $this->programRepository->save($program);

        $role = new Role("role");
        $this->roleRepository->save($role);

        $user1 = new User();
        $user1->setFirstName('First');
        $user1->setLastName('Last');
        $user1->addRole($role);
        $user1->setApproved(true);
        $this->userRepository->save($user1);

        $subeventsApplication1 = new SubeventsApplication();
        $subeventsApplication1->setUser($user1);
        $subeventsApplication1->setSubevents(new ArrayCollection([$subevent]));
        $subeventsApplication1->setFee(0);
        $subeventsApplication1->setApplicationDate(new DateTimeImmutable());
        $subeventsApplication1->setState(ApplicationState::PAID_FREE);
        $subeventsApplication1->setValidFrom(new DateTimeImmutable());
        $this->applicationRepository->save($subeventsApplication1);

        $user2 = new User();
        $user2->setFirstName('First');
        $user2->setLastName('Last');
        $user2->addRole($role);
        $user2->setApproved(true);
        $this->userRepository->save($user2);

        $subeventsApplication2 = new SubeventsApplication();
        $subeventsApplication2->setUser($user2);
        $subeventsApplication2->setSubevents(new ArrayCollection([$subevent]));
        $subeventsApplication2->setFee(0);
        $subeventsApplication2->setApplicationDate(new DateTimeImmutable());
        $subeventsApplication2->setState(ApplicationState::PAID_FREE);
        $subeventsApplication2->setValidFrom(new DateTimeImmutable());
        $this->applicationRepository->save($subeventsApplication2);

        $this->commandBus->handle(new RegisterProgram($user1, $program, false));
        $programApplication1 = $this->programApplicationRepository->findUserProgramApplication($user1, $program);
        $this->assertEquals($user1, $programApplication1->getUser());
        $this->assertEquals($program, $programApplication1->getProgram());
        $this->assertFalse($programApplication1->isAlternate());

        $this->commandBus->handle(new RegisterProgram($user2, $program, false));
        $programApplication2 = $this->programApplicationRepository->findUserProgramApplication($user2, $program);
        $this->assertEquals($user2, $programApplication2->getUser());
        $this->assertEquals($program, $programApplication2->getProgram());
        $this->assertTrue($programApplication2->isAlternate());
    }

    public function testAlterantesNotAllowed(): void
    {
        $subevent = new Subevent();
        $subevent->setName("subevent");
        $this->subeventRepository->save($subevent);

        $block = new Block("block", 60, 1, false, ProgramMandatoryType::VOLUNTARY, $subevent, null);
        $this->blockRepository->save($block);

        $program = new Program($block, null, new DateTimeImmutable('2020-01-01 08:00'));
        $this->programRepository->save($program);

        $role = new Role("role");
        $this->roleRepository->save($role);

        $user1 = new User();
        $user1->setFirstName('First');
        $user1->setLastName('Last');
        $user1->addRole($role);
        $user1->setApproved(true);
        $this->userRepository->save($user1);

        $subeventsApplication1 = new SubeventsApplication();
        $subeventsApplication1->setUser($user1);
        $subeventsApplication1->setSubevents(new ArrayCollection([$subevent]));
        $subeventsApplication1->setFee(0);
        $subeventsApplication1->setApplicationDate(new DateTimeImmutable());
        $subeventsApplication1->setState(ApplicationState::PAID_FREE);
        $subeventsApplication1->setValidFrom(new DateTimeImmutable());
        $this->applicationRepository->save($subeventsApplication1);

        $user2 = new User();
        $user2->setFirstName('First');
        $user2->setLastName('Last');
        $user2->addRole($role);
        $user2->setApproved(true);
        $this->userRepository->save($user2);

        $subeventsApplication2 = new SubeventsApplication();
        $subeventsApplication2->setUser($user2);
        $subeventsApplication2->setSubevents(new ArrayCollection([$subevent]));
        $subeventsApplication2->setFee(0);
        $subeventsApplication2->setApplicationDate(new DateTimeImmutable());
        $subeventsApplication2->setState(ApplicationState::PAID_FREE);
        $subeventsApplication2->setValidFrom(new DateTimeImmutable());
        $this->applicationRepository->save($subeventsApplication2);

        $this->commandBus->handle(new RegisterProgram($user1, $program, false));
        $programApplication1 = $this->programApplicationRepository->findUserProgramApplication($user1, $program);
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

    public function testWrongRole(): void
    {
        $subevent = new Subevent();
        $subevent->setName("subevent");
        $this->subeventRepository->save($subevent);

        $category = new Category("category");
        $this->categoryRepository->save($category);

        $block = new Block("block", 60, null, false, ProgramMandatoryType::VOLUNTARY, $subevent, $category);
        $this->blockRepository->save($block);

        $program = new Program($block, null, new DateTimeImmutable('2020-01-01 08:00'));
        $this->programRepository->save($program);

        $role = new Role("role");
        $this->roleRepository->save($role);

        $user = new User();
        $user->setFirstName('First');
        $user->setLastName('Last');
        $user->addRole($role);
        $user->setApproved(true);
        $this->userRepository->save($user);

        $subeventsApplication = new SubeventsApplication();
        $subeventsApplication->setUser($user);
        $subeventsApplication->setSubevents(new ArrayCollection([$subevent]));
        $subeventsApplication->setFee(0);
        $subeventsApplication->setApplicationDate(new DateTimeImmutable());
        $subeventsApplication->setState(ApplicationState::PAID_FREE);
        $subeventsApplication->setValidFrom(new DateTimeImmutable());
        $this->applicationRepository->save($subeventsApplication);

        $this->expectException(UserNotAllowedProgramException::class);
        try {
            $this->commandBus->handle(new RegisterProgram($user, $program, false));
        } catch (HandlerFailedException $e) {
            throw $e->getPrevious();
        }
    }

    public function testWrongSubevent(): void
    {
        $subevent = new Subevent();
        $subevent->setName("subevent");
        $this->subeventRepository->save($subevent);

        $block = new Block("block", 60, null, false, ProgramMandatoryType::VOLUNTARY, $subevent, null);
        $this->blockRepository->save($block);

        $program = new Program($block, null, new DateTimeImmutable('2020-01-01 08:00'));
        $this->programRepository->save($program);

        $role = new Role("role");
        $this->roleRepository->save($role);

        $user = new User();
        $user->setFirstName('First');
        $user->setLastName('Last');
        $user->addRole($role);
        $user->setApproved(true);
        $this->userRepository->save($user);

        $this->expectException(UserNotAllowedProgramException::class);
        try {
            $this->commandBus->handle(new RegisterProgram($user, $program, false));
        } catch (HandlerFailedException $e) {
            throw $e->getPrevious();
        }
    }

    public function testNotPaidSubevent(): void
    {
        $subevent = new Subevent();
        $subevent->setName("subevent");
        $this->subeventRepository->save($subevent);

        $block = new Block("block", 60, null, false, ProgramMandatoryType::VOLUNTARY, $subevent, null);
        $this->blockRepository->save($block);

        $program = new Program($block, null, new DateTimeImmutable('2020-01-01 08:00'));
        $this->programRepository->save($program);

        $role = new Role("role");
        $this->roleRepository->save($role);

        $user = new User();
        $user->setFirstName('First');
        $user->setLastName('Last');
        $user->addRole($role);
        $user->setApproved(true);
        $this->userRepository->save($user);

        $subeventsApplication = new SubeventsApplication();
        $subeventsApplication->setUser($user);
        $subeventsApplication->setSubevents(new ArrayCollection([$subevent]));
        $subeventsApplication->setFee(0);
        $subeventsApplication->setApplicationDate(new DateTimeImmutable());
        $subeventsApplication->setState(ApplicationState::WAITING_FOR_PAYMENT);
        $subeventsApplication->setValidFrom(new DateTimeImmutable());
        $this->applicationRepository->save($subeventsApplication);

        $this->expectException(UserNotAllowedProgramException::class);
        try {
            $this->commandBus->handle(new RegisterProgram($user, $program, false));
        } catch (HandlerFailedException $e) {
            throw $e->getPrevious();
        }
    }

    public function testAlreadyAttendsProgram(): void
    {
        $subevent = new Subevent();
        $subevent->setName("subevent");
        $this->subeventRepository->save($subevent);

        $block = new Block("block", 60, 1, true, ProgramMandatoryType::VOLUNTARY, $subevent, null);
        $this->blockRepository->save($block);

        $program = new Program($block, null, new DateTimeImmutable('2020-01-01 08:00'));
        $this->programRepository->save($program);

        $role = new Role("role");
        $this->roleRepository->save($role);

        $user = new User();
        $user->setFirstName('First');
        $user->setLastName('Last');
        $user->addRole($role);
        $user->setApproved(true);
        $this->userRepository->save($user);

        $subeventsApplication = new SubeventsApplication();
        $subeventsApplication->setUser($user);
        $subeventsApplication->setSubevents(new ArrayCollection([$subevent]));
        $subeventsApplication->setFee(0);
        $subeventsApplication->setApplicationDate(new DateTimeImmutable());
        $subeventsApplication->setState(ApplicationState::PAID_FREE);
        $subeventsApplication->setValidFrom(new DateTimeImmutable());
        $this->applicationRepository->save($subeventsApplication);

        $this->commandBus->handle(new RegisterProgram($user, $program, false));
        $programApplication1 = $this->programApplicationRepository->findUserProgramApplication($user, $program);
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

    public function testAlreadyAttendsBlock(): void
    {
        $subevent = new Subevent();
        $subevent->setName("subevent");
        $this->subeventRepository->save($subevent);

        $block = new Block("block", 60, 1, true, ProgramMandatoryType::VOLUNTARY, $subevent, null);
        $this->blockRepository->save($block);

        $program1 = new Program($block, null, new DateTimeImmutable('2020-01-01 08:00'));
        $this->programRepository->save($program1);

        $program2 = new Program($block, null, new DateTimeImmutable('2020-01-01 10:00'));
        $this->programRepository->save($program2);

        $role = new Role("role");
        $this->roleRepository->save($role);

        $user = new User();
        $user->setFirstName('First');
        $user->setLastName('Last');
        $user->addRole($role);
        $user->setApproved(true);
        $this->userRepository->save($user);

        $subeventsApplication = new SubeventsApplication();
        $subeventsApplication->setUser($user);
        $subeventsApplication->setSubevents(new ArrayCollection([$subevent]));
        $subeventsApplication->setFee(0);
        $subeventsApplication->setApplicationDate(new DateTimeImmutable());
        $subeventsApplication->setState(ApplicationState::PAID_FREE);
        $subeventsApplication->setValidFrom(new DateTimeImmutable());
        $this->applicationRepository->save($subeventsApplication);

        $this->commandBus->handle(new RegisterProgram($user, $program1, false));
        $programApplication1 = $this->programApplicationRepository->findUserProgramApplication($user, $program1);
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

    public function testNonRegistered(): void
    {
        $role = new Role(Role::NONREGISTERED);
        $this->roleRepository->save($role);
    }

    public function testNotApproved(): void
    {
        //        $userNotApproved = new User();
//        $userNotApproved->setFirstName('First');
//        $userNotApproved->setLastName('Last');
//        $userNotApproved->addRole($roleCategory);
//        $userNotApproved->setApproved(false);
//        $this->userRepository->save($userNotApproved);
    }



    /**
     * @return string[]
     */
    protected function getTestedAggregateRoots(): array
    {
        return [Block::class, Settings::class];
    }

    protected function _before(): void
    {
        $this->tester->useConfigFiles([__DIR__ . '/RegisterProgramHandlerTest.neon']);
        parent::_before();

        $this->settingsService = $this->tester->grabService(ISettingsService::class);
        $this->blockRepository = $this->tester->grabService(BlockRepository::class);
        $this->subeventRepository = $this->tester->grabService(SubeventRepository::class);
        $this->userRepository = $this->tester->grabService(UserRepository::class);
        $this->categoryRepository = $this->tester->grabService(CategoryRepository::class);
        $this->roleRepository = $this->tester->grabService(RoleRepository::class);
        $this->programRepository = $this->tester->grabService(ProgramRepository::class);
        $this->applicationRepository = $this->tester->grabService(ApplicationRepository::class);
        $this->programApplicationRepository = $this->tester->grabService(ProgramApplicationRepository::class);
    }
}