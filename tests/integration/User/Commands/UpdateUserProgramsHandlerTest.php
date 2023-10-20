<?php

declare(strict_types=1);

namespace App\Model\User\Commands\Handlers;

use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Acl\Role;
use App\Model\Application\ApplicationFactory;
use App\Model\Application\Repositories\ApplicationRepository;
use App\Model\Application\RolesApplication;
use App\Model\Enums\ApplicationState;
use App\Model\Enums\ProgramMandatoryType;
use App\Model\Mailing\Mail;
use App\Model\Mailing\MailQueue;
use App\Model\Mailing\Repositories\TemplateRepository;
use App\Model\Mailing\Template;
use App\Model\Mailing\TemplateFactory;
use App\Model\Program\Block;
use App\Model\Program\Category;
use App\Model\Program\Program;
use App\Model\Program\Repositories\BlockRepository;
use App\Model\Program\Repositories\CategoryRepository;
use App\Model\Program\Repositories\ProgramRepository;
use App\Model\Settings\Repositories\SettingsRepository;
use App\Model\Settings\Settings;
use App\Model\Structure\Repositories\SubeventRepository;
use App\Model\Structure\Subevent;
use App\Model\User\Commands\UpdateUserPrograms;
use App\Model\User\Repositories\UserRepository;
use App\Model\User\User;
use CommandHandlerTest;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;

final class UpdateUserProgramsHandlerTest extends CommandHandlerTest
{
    private BlockRepository $blockRepository;

    private SubeventRepository $subeventRepository;

    private UserRepository $userRepository;

    private RoleRepository $roleRepository;

    private ProgramRepository $programRepository;

    private ApplicationRepository $applicationRepository;

    private CategoryRepository $categoryRepository;

    private SettingsRepository $settingsRepository;

    private TemplateRepository $templateRepository;

    /**
     * Aktualizace programů schváleného a neschváleného uživatele.
     */
    public function testUserApproved(): void
    {
        $subevent = new Subevent();
        $subevent->setName('subevent');
        $this->subeventRepository->save($subevent);

        $block = new Block('block', 60, null, false, ProgramMandatoryType::AUTO_REGISTERED);
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
        $user->setEmail('mail@mail.cz');
        $user->addRole($role);
        $user->setApproved(false);
        $this->userRepository->save($user);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user, $role);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user, $subevent);

        $this->assertEquals(0, $program->getAttendeesCount());

        $this->commandBus->handle(new UpdateUserPrograms($user));

        $this->assertEquals(0, $program->getAttendeesCount());

        $user->setApproved(true);
        $this->userRepository->save($user);

        $this->commandBus->handle(new UpdateUserPrograms($user));

        $this->assertEquals(1, $program->getAttendeesCount());

        $user->setApproved(false);
        $this->userRepository->save($user);

        $this->commandBus->handle(new UpdateUserPrograms($user));

        $this->assertEquals(0, $program->getAttendeesCount());
    }

    /**
     * Aktualizace programů při změně podakcí.
     */
    public function testUserSubeventsChanged(): void
    {
        $subevent1 = new Subevent();
        $subevent1->setName('subevent1');
        $this->subeventRepository->save($subevent1);

        $subevent2 = new Subevent();
        $subevent2->setName('subevent2');
        $this->subeventRepository->save($subevent2);

        $block = new Block('block', 60, null, false, ProgramMandatoryType::AUTO_REGISTERED);
        $block->setSubevent($subevent2);
        $this->blockRepository->save($block);

        $program = new Program(new DateTimeImmutable('2020-01-01 08:00'));
        $program->setBlock($block);
        $this->programRepository->save($program);

        $role = new Role('role');
        $this->roleRepository->save($role);

        $user = new User();
        $user->setFirstName('First');
        $user->setLastName('Last');
        $user->setEmail('mail@mail.cz');
        $user->addRole($role);
        $user->setApproved(true);
        $this->userRepository->save($user);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user, $role);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user, $subevent1);

        $this->assertEquals(0, $program->getAttendeesCount());

        $this->commandBus->handle(new UpdateUserPrograms($user));

        $this->assertEquals(0, $program->getAttendeesCount());

        // přidání podakce, která se na program může přihlásit
        $subeventsApplication2 = ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user, $subevent2);

        $this->commandBus->handle(new UpdateUserPrograms($user));

        $this->assertEquals(1, $program->getAttendeesCount());

        // odebrání podakce, která se na program může přihlásit
        $subeventsApplication2->setState(ApplicationState::CANCELED);
        $this->applicationRepository->save($subeventsApplication2);

        $this->commandBus->handle(new UpdateUserPrograms($user));

        $this->assertEquals(0, $program->getAttendeesCount());
    }

    /**
     * Aktualizace programů při změně rolí.
     */
    public function testUserRolesChanged(): void
    {
        $subevent = new Subevent();
        $subevent->setName('subevent1');
        $this->subeventRepository->save($subevent);

        $role1 = new Role('role1');
        $this->roleRepository->save($role1);

        $role2 = new Role('role2');
        $this->roleRepository->save($role2);

        $category = new Category('category');
        $category->addRegisterableRole($role2);
        $this->categoryRepository->save($category);

        $block = new Block('block', 60, null, false, ProgramMandatoryType::AUTO_REGISTERED);
        $block->setSubevent($subevent);
        $block->setCategory($category);
        $this->blockRepository->save($block);

        $program = new Program(new DateTimeImmutable('2020-01-01 08:00'));
        $program->setBlock($block);
        $this->programRepository->save($program);

        $user = new User();
        $user->setFirstName('First');
        $user->setLastName('Last');
        $user->setEmail('mail@mail.cz');
        $user->addRole($role1);
        $user->setApproved(true);
        $this->userRepository->save($user);

        $rolesApplication = ApplicationFactory::createRolesApplication($this->applicationRepository, $user, $role1);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user, $subevent);

        $this->assertEquals(0, $program->getAttendeesCount());

        $this->commandBus->handle(new UpdateUserPrograms($user));

        $this->assertEquals(0, $program->getAttendeesCount());

        // přidání role, která se na program může přihlásit
        $rolesApplication->setState(ApplicationState::CANCELED);
        $this->applicationRepository->save($rolesApplication);

        $user->addRole($role2);
        $this->userRepository->save($user);

        $rolesApplication = new RolesApplication($user);
        $rolesApplication->setRoles(new ArrayCollection([$role1, $role2]));
        $rolesApplication->setFee(10);
        $rolesApplication->setApplicationDate(new DateTimeImmutable());
        $rolesApplication->setState(ApplicationState::PAID);
        $rolesApplication->setValidFrom(new DateTimeImmutable());
        $this->applicationRepository->save($rolesApplication);
        $rolesApplication->setApplicationId($rolesApplication->getId());
        $this->applicationRepository->save($rolesApplication);

        $this->commandBus->handle(new UpdateUserPrograms($user));

        $this->assertEquals(1, $program->getAttendeesCount());

        // odebrání role, která se na program může přihlásit
        $rolesApplication->setState(ApplicationState::CANCELED);
        $this->applicationRepository->save($rolesApplication);

        $user->removeRole($role2);
        $this->userRepository->save($user);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user, $role1);

        $this->commandBus->handle(new UpdateUserPrograms($user));

        $this->assertEquals(0, $program->getAttendeesCount());
    }

    /** @return string[] */
    protected function getTestedAggregateRoots(): array
    {
        return [User::class, Settings::class, Mail::class, MailQueue::class, Template::class];
    }

    protected function _before(): void
    {
        $this->tester->useConfigFiles([__DIR__ . '/UpdateUserProgramsHandlerTest.neon']);

        parent::_before();

        $this->blockRepository       = $this->tester->grabService(BlockRepository::class);
        $this->subeventRepository    = $this->tester->grabService(SubeventRepository::class);
        $this->userRepository        = $this->tester->grabService(UserRepository::class);
        $this->roleRepository        = $this->tester->grabService(RoleRepository::class);
        $this->programRepository     = $this->tester->grabService(ProgramRepository::class);
        $this->applicationRepository = $this->tester->grabService(ApplicationRepository::class);
        $this->categoryRepository    = $this->tester->grabService(CategoryRepository::class);
        $this->settingsRepository    = $this->tester->grabService(SettingsRepository::class);
        $this->templateRepository    = $this->tester->grabService(TemplateRepository::class);

        $this->settingsRepository->save(new Settings(Settings::IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT, (string) false));
        $this->settingsRepository->save(new Settings(Settings::SEMINAR_NAME, 'test'));

        TemplateFactory::createTemplate($this->templateRepository, Template::PROGRAM_REGISTERED);
        TemplateFactory::createTemplate($this->templateRepository, Template::PROGRAM_UNREGISTERED);
    }
}
