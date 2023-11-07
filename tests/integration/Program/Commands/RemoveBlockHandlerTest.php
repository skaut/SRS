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
use App\Model\Program\Commands\RemoveBlock;
use App\Model\Program\Program;
use App\Model\Program\ProgramApplication;
use App\Model\Program\Repositories\BlockRepository;
use App\Model\Program\Repositories\CategoryRepository;
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

final class RemoveBlockHandlerTest extends CommandHandlerTest
{
    private SubeventRepository $subeventRepository;

    private UserRepository $userRepository;

    private CategoryRepository $categoryRepository;

    private RoleRepository $roleRepository;

    private ProgramRepository $programRepository;

    private ApplicationRepository $applicationRepository;

    private ProgramApplicationRepository $programApplicationRepository;

    private BlockRepository $blockRepository;

    private SettingsRepository $settingsRepository;

    private TemplateRepository $templateRepository;

    /**
     * Odstranění bloku - odstraní se i jeho programy a účastníci.
     *
     * @throws OptimisticLockException
     * @throws Throwable
     */
    public function testRemoveBlock(): void
    {
        $subevent = new Subevent();
        $subevent->setName('subevent');
        $this->subeventRepository->save($subevent);

        $role = new Role('role1');
        $this->roleRepository->save($role);

        $category = new Category('category');
        $category->addRegisterableRole($role);
        $this->categoryRepository->save($category);

        $user = new User();
        $user->setFirstName('First');
        $user->setLastName('Last');
        $user->setEmail('mail@mail.cz');
        $user->addRole($role);
        $user->setApproved(true);
        $this->userRepository->save($user);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user, $role);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user, $subevent);

        $block = new Block('block', 60, 2, true, ProgramMandatoryType::VOLUNTARY);
        $block->setSubevent($subevent);
        $block->setCategory($category);
        $this->blockRepository->save($block);

        $program = new Program(new DateTimeImmutable('2020-01-01 08:00'));
        $program->setBlock($block);
        $this->programRepository->save($program);

        $this->programApplicationRepository->save(new ProgramApplication($user, $program));

        $this->assertContains($block, $this->blockRepository->findAll());
        $this->assertContains($block, $category->getBlocks());
        $this->assertContains($block, $subevent->getBlocks());

        $this->commandBus->handle(new RemoveBlock($block));

        $this->assertNotContains($block, $this->blockRepository->findAll());
        $this->assertNotContains($block, $category->getBlocks());
        $this->assertNotContains($block, $subevent->getBlocks());
    }

    /** @return string[] */
    protected function getTestedAggregateRoots(): array
    {
        return [Block::class, Settings::class, Mail::class, MailQueue::class, Template::class];
    }

    protected function _before(): void
    {
        $this->tester->useConfigFiles([__DIR__ . '/RemoveBlockHandlerTest.neon']);

        parent::_before();

        $this->subeventRepository           = $this->tester->grabService(SubeventRepository::class);
        $this->userRepository               = $this->tester->grabService(UserRepository::class);
        $this->categoryRepository           = $this->tester->grabService(CategoryRepository::class);
        $this->roleRepository               = $this->tester->grabService(RoleRepository::class);
        $this->programRepository            = $this->tester->grabService(ProgramRepository::class);
        $this->applicationRepository        = $this->tester->grabService(ApplicationRepository::class);
        $this->programApplicationRepository = $this->tester->grabService(ProgramApplicationRepository::class);
        $this->blockRepository              = $this->tester->grabService(BlockRepository::class);
        $this->settingsRepository           = $this->tester->grabService(SettingsRepository::class);
        $this->templateRepository           = $this->tester->grabService(TemplateRepository::class);

        $this->settingsRepository->save(new Settings(Settings::SEMINAR_NAME, 'test'));

        TemplateFactory::createTemplate($this->templateRepository, Template::PROGRAM_UNREGISTERED);
    }
}
