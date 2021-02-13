<?php

declare(strict_types=1);

namespace App\Model\Program\Commands\Handlers;

use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Acl\Role;
use App\Model\Application\ApplicationFactory;
use App\Model\Application\Repositories\ApplicationRepository;
use App\Model\Enums\ProgramMandatoryType;
use App\Model\Program\Block;
use App\Model\Program\Category;
use App\Model\Program\Commands\RemoveCategory;
use App\Model\Program\Program;
use App\Model\Program\Repositories\BlockRepository;
use App\Model\Program\Repositories\CategoryRepository;
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
use Doctrine\ORM\ORMException;
use Throwable;

final class RemoveCategoryHandlerTest extends CommandHandlerTest
{
    private SubeventRepository $subeventRepository;

    private UserRepository $userRepository;

    private CategoryRepository $categoryRepository;

    private RoleRepository $roleRepository;

    private ProgramRepository $programRepository;

    private ApplicationRepository $applicationRepository;

    private BlockRepository $blockRepository;

    private SettingsRepository $settingsRepository;

    /**
     * Odstranění kategorie - automaticky přihlašovaní, kteří jsou nově oprávněni jsou přihlášeni.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Throwable
     */
    public function testRemoveCategory(): void
    {
        $subevent = new Subevent();
        $subevent->setName('subevent');
        $this->subeventRepository->save($subevent);

        $role1 = new Role('role1');
        $this->roleRepository->save($role1);

        $role2 = new Role('role2');
        $this->roleRepository->save($role2);

        $category = new Category('category');
        $category->addRegisterableRole($role1);
        $this->categoryRepository->save($category);

        $user = new User();
        $user->setFirstName('First');
        $user->setLastName('Last');
        $user->addRole($role2);
        $user->setApproved(true);
        $this->userRepository->save($user);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user, $role2);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user, $subevent);

        $block = new Block('block', 60, null, false, ProgramMandatoryType::AUTO_REGISTERED);
        $block->setSubevent($subevent);
        $block->setCategory($category);
        $this->blockRepository->save($block);

        $program = new Program(new DateTimeImmutable('2020-01-01 08:00'));
        $program->setBlock($block);
        $this->programRepository->save($program);

        $this->assertContains($category, $this->categoryRepository->findAll());
        $this->assertEquals($category, $block->getCategory());
        $this->assertEquals(0, $program->getAttendeesCount());

        $this->commandBus->handle(new RemoveCategory($category));

        $this->assertNotContains($category, $this->categoryRepository->findAll());
        $this->assertNull($block->getCategory());
        $this->assertEquals(1, $program->getAttendeesCount());
    }

    /**
     * @return string[]
     */
    protected function getTestedAggregateRoots(): array
    {
        return [Category::class, Settings::class];
    }

    protected function _before(): void
    {
        $this->tester->useConfigFiles([__DIR__ . '/RemoveCategoryHandlerTest.neon']);
        parent::_before();

        $this->subeventRepository    = $this->tester->grabService(SubeventRepository::class);
        $this->userRepository        = $this->tester->grabService(UserRepository::class);
        $this->categoryRepository    = $this->tester->grabService(CategoryRepository::class);
        $this->roleRepository        = $this->tester->grabService(RoleRepository::class);
        $this->programRepository     = $this->tester->grabService(ProgramRepository::class);
        $this->applicationRepository = $this->tester->grabService(ApplicationRepository::class);
        $this->blockRepository       = $this->tester->grabService(BlockRepository::class);
        $this->settingsRepository = $this->tester->grabService(SettingsRepository::class);

        $this->settingsRepository->save(new Settings(Settings::IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT, (string) false));
        $this->settingsRepository->save(new Settings(Settings::SEMINAR_NAME, 'test'));
    }
}
