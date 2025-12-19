<?php

declare(strict_types=1);

namespace App\Model\Program\Commands\Handlers;

use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Acl\Role;
use App\Model\Application\ApplicationFactory;
use App\Model\Application\Repositories\ApplicationRepository;
use App\Model\Enums\ProgramMandatoryType;
use App\Model\Mailing\Mail;
use App\Model\Mailing\MailQueue;
use App\Model\Mailing\Repositories\TemplateRepository;
use App\Model\Mailing\Template;
use App\Model\Mailing\TemplateFactory;
use App\Model\Program\Block;
use App\Model\Program\Category;
use App\Model\Program\Commands\SaveCategory;
use App\Model\Program\Program;
use App\Model\Program\ProgramApplication;
use App\Model\Program\Repositories\BlockRepository;
use App\Model\Program\Repositories\ProgramApplicationRepository;
use App\Model\Program\Repositories\ProgramRepository;
use App\Model\Settings\Repositories\SettingsRepository;
use App\Model\Settings\Settings;
use App\Model\Structure\Repositories\SubeventRepository;
use App\Model\Structure\Subevent;
use App\Model\User\Repositories\UserRepository;
use App\Model\User\User;
use CommandHandlerTest;
use DateTimeImmutable;
use Doctrine\ORM\OptimisticLockException;
use Throwable;

final class SaveCategoryHandlerTest extends CommandHandlerTest
{
    private BlockRepository $blockRepository;

    private SubeventRepository $subeventRepository;

    private UserRepository $userRepository;

    private RoleRepository $roleRepository;

    private ProgramRepository $programRepository;

    private ApplicationRepository $applicationRepository;

    private ProgramApplicationRepository $programApplicationRepository;

    private SettingsRepository $settingsRepository;

    private TemplateRepository $templateRepository;

    /**
     * Změna rolí u kategorie - neoprávnění účastníci a náhradníci jsou odhlášeni, automaticky přihlašovaní přihlášeni.
     *
     * @throws OptimisticLockException
     * @throws Throwable
     */
    public function testChangeRegisterableRoles(): void
    {
        $subevent = new Subevent();
        $subevent->setName('subevent');
        $this->subeventRepository->save($subevent);

        $role1 = new Role('role1');
        $this->roleRepository->save($role1);

        $role2 = new Role('role2');
        $this->roleRepository->save($role2);

        $role3 = new Role('role3');
        $this->roleRepository->save($role3);

        $category = new Category('category');
        $category->addRegisterableRole($role1);
        $category->addRegisterableRole($role2);
        $this->commandBus->handle(new SaveCategory($category, null));

        $block = new Block('block', 60, 2, true, ProgramMandatoryType::AUTO_REGISTERED);
        $block->setSubevent($subevent);
        $block->setCategory($category);
        $this->blockRepository->save($block);

        $program = new Program(new DateTimeImmutable('2020-01-01 08:00'));
        $program->setBlock($block);
        $this->programRepository->save($program);

        $user1 = new User();
        $user1->setFirstName('First');
        $user1->setLastName('Last');
        $user1->setEmail('mail@mail.cz');
        $user1->addRole($role1);
        $user1->setApproved(true);
        $this->userRepository->save($user1);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user1, $role1);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user1, $subevent);

        $user2 = new User();
        $user2->setFirstName('First');
        $user2->setLastName('Last');
        $user2->setEmail('mail@mail.cz');
        $user2->addRole($role2);
        $user2->setApproved(true);
        $this->userRepository->save($user2);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user2, $role2);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user2, $subevent);

        $user3 = new User();
        $user3->setFirstName('First');
        $user3->setLastName('Last');
        $user3->setEmail('mail@mail.cz');
        $user3->addRole($role3);
        $user3->setApproved(true);
        $this->userRepository->save($user3);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user3, $role3);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user3, $subevent);

        $user4 = new User();
        $user4->setFirstName('First');
        $user4->setLastName('Last');
        $user4->setEmail('mail@mail.cz');
        $user4->addRole($role2);
        $user4->setApproved(true);
        $this->userRepository->save($user4);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user4, $role2);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user4, $subevent);

        $this->assertNull($this->programApplicationRepository->findByUserAndProgram($user1, $program));
        $this->assertNull($this->programApplicationRepository->findByUserAndProgram($user2, $program));
        $this->assertNull($this->programApplicationRepository->findByUserAndProgram($user3, $program));
        $this->assertNull($this->programApplicationRepository->findByUserAndProgram($user4, $program));

        $this->programApplicationRepository->save(new ProgramApplication($user1, $program));
        $this->programApplicationRepository->save(new ProgramApplication($user2, $program));
        $this->programApplicationRepository->save(new ProgramApplication($user4, $program));

        $programApplication1 = $this->programApplicationRepository->findByUserAndProgram($user1, $program);
        $this->assertNotNull($programApplication1);
        $this->assertFalse($programApplication1->isAlternate());
        $programApplication2 = $this->programApplicationRepository->findByUserAndProgram($user2, $program);
        $this->assertNotNull($programApplication2);
        $this->assertFalse($programApplication2->isAlternate());
        $programApplication3 = $this->programApplicationRepository->findByUserAndProgram($user3, $program);
        $this->assertNull($programApplication3);
        $programApplication4 = $this->programApplicationRepository->findByUserAndProgram($user4, $program);
        $this->assertNotNull($programApplication4);
        $this->assertTrue($programApplication4->isAlternate());

        $categoryOld = clone $category;
        $category->removeRegisterableRole($role2);
        $this->commandBus->handle(new SaveCategory($category, $categoryOld));

        $this->assertNotNull($this->programApplicationRepository->findByUserAndProgram($user1, $program));
        $this->assertNull($this->programApplicationRepository->findByUserAndProgram($user2, $program));
        $this->assertNull($this->programApplicationRepository->findByUserAndProgram($user3, $program));
        $this->assertNull($this->programApplicationRepository->findByUserAndProgram($user4, $program));

        $categoryOld = clone $category;
        $category->addRegisterableRole($role3);
        $this->commandBus->handle(new SaveCategory($category, $categoryOld));

        $this->assertNotNull($this->programApplicationRepository->findByUserAndProgram($user1, $program));
        $this->assertNull($this->programApplicationRepository->findByUserAndProgram($user2, $program));
        $this->assertNotNull($this->programApplicationRepository->findByUserAndProgram($user3, $program));
        $this->assertNull($this->programApplicationRepository->findByUserAndProgram($user4, $program));
    }

    /** @return string[] */
    protected function getTestedAggregateRoots(): array
    {
        return [Category::class, Settings::class, Mail::class, MailQueue::class, Template::class];
    }

    protected function _before(): void
    {
        $this->getModule('IntegrationTester')->useConfigFiles([__DIR__ . '/SaveCategoryHandlerTest.neon']);

        parent::_before();

        $this->blockRepository              = $this->getModule('IntegrationTester')->grabService(BlockRepository::class);
        $this->subeventRepository           = $this->getModule('IntegrationTester')->grabService(SubeventRepository::class);
        $this->userRepository               = $this->getModule('IntegrationTester')->grabService(UserRepository::class);
        $this->roleRepository               = $this->getModule('IntegrationTester')->grabService(RoleRepository::class);
        $this->programRepository            = $this->getModule('IntegrationTester')->grabService(ProgramRepository::class);
        $this->applicationRepository        = $this->getModule('IntegrationTester')->grabService(ApplicationRepository::class);
        $this->programApplicationRepository = $this->getModule('IntegrationTester')->grabService(ProgramApplicationRepository::class);
        $this->settingsRepository           = $this->getModule('IntegrationTester')->grabService(SettingsRepository::class);
        $this->templateRepository           = $this->getModule('IntegrationTester')->grabService(TemplateRepository::class);

        $this->settingsRepository->save(new Settings(Settings::IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT, (string) false));
        $this->settingsRepository->save(new Settings(Settings::SEMINAR_NAME, 'test'));

        TemplateFactory::createTemplate($this->templateRepository, Template::PROGRAM_REGISTERED);
        TemplateFactory::createTemplate($this->templateRepository, Template::PROGRAM_UNREGISTERED);
    }
}
