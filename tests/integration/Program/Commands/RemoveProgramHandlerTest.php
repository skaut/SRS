<?php

declare(strict_types=1);

namespace App\Model\Program\Commands\Handlers;

use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Acl\Role;
use App\Model\Application\ApplicationFactory;
use App\Model\Application\Repositories\ApplicationRepository;
use App\Model\Enums\ProgramMandatoryType;
use App\Model\Program\Block;
use App\Model\Program\Commands\RemoveProgram;
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

final class RemoveProgramHandlerTest extends CommandHandlerTest
{
    private SubeventRepository $subeventRepository;

    private UserRepository $userRepository;

    private RoleRepository $roleRepository;

    private ProgramRepository $programRepository;

    private ApplicationRepository $applicationRepository;

    private ProgramApplicationRepository $programApplicationRepository;

    private BlockRepository $blockRepository;

    private SettingsRepository $settingsRepository;

    /**
     * Odstranění programu - odstraní se i jeho účastníci.
     *
     * @throws OptimisticLockException
     * @throws Throwable
     *
     * @skip temporary skip because of Translator
     */
    public function testRemoveProgram(): void
    {
        $subevent = new Subevent();
        $subevent->setName('subevent');
        $this->subeventRepository->save($subevent);

        $role = new Role('role1');
        $this->roleRepository->save($role);

        $user = new User();
        $user->setFirstName('First');
        $user->setLastName('Last');
        $user->addRole($role);
        $user->setApproved(true);
        $this->userRepository->save($user);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user, $role);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user, $subevent);

        $block = new Block('block', 60, 2, true, ProgramMandatoryType::VOLUNTARY);
        $block->setSubevent($subevent);
        $this->blockRepository->save($block);

        $program = new Program(new DateTimeImmutable('2020-01-01 08:00'));
        $program->setBlock($block);
        $this->programRepository->save($program);

        $this->programApplicationRepository->save(new ProgramApplication($user, $program));

        $this->assertContains($program, $this->programRepository->findAll());
        $this->assertContains($program, $block->getPrograms());

        $this->commandBus->handle(new RemoveProgram($program));

        $this->assertNotContains($program, $this->programRepository->findAll());
        $this->assertNotContains($program, $block->getPrograms());
    }

    /** @return string[] */
    protected function getTestedAggregateRoots(): array
    {
        return [Program::class, Settings::class];
    }

    protected function _before(): void
    {
        $this->tester->useConfigFiles([__DIR__ . '/RemoveProgramHandlerTest.neon']);

        parent::_before();

        $this->subeventRepository           = $this->tester->grabService(SubeventRepository::class);
        $this->userRepository               = $this->tester->grabService(UserRepository::class);
        $this->roleRepository               = $this->tester->grabService(RoleRepository::class);
        $this->programRepository            = $this->tester->grabService(ProgramRepository::class);
        $this->applicationRepository        = $this->tester->grabService(ApplicationRepository::class);
        $this->programApplicationRepository = $this->tester->grabService(ProgramApplicationRepository::class);
        $this->blockRepository              = $this->tester->grabService(BlockRepository::class);
        $this->settingsRepository           = $this->tester->grabService(SettingsRepository::class);

        $this->settingsRepository->save(new Settings(Settings::SEMINAR_NAME, 'test'));
    }
}
