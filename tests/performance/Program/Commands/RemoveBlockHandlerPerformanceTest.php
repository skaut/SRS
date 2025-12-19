<?php

declare(strict_types=1);

namespace App\Model\Program\Commands\Handlers;

use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Acl\Role;
use App\Model\Application\ApplicationFactory;
use App\Model\Application\Repositories\ApplicationRepository;
use App\Model\Enums\ProgramMandatoryType;
use App\Model\Program\Block;
use App\Model\Program\Commands\RemoveBlock;
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

use function microtime;
use function rand;

final class RemoveBlockHandlerPerformanceTest extends CommandHandlerTest
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
     * Odstranění bloku s velkým množstvím programů a účastníků.
     *
     * @throws OptimisticLockException
     * @throws Throwable
     */
    public function testRemoveBlock(): void
    {
        $subevent = new Subevent();
        $subevent->setName('subevent');
        $this->subeventRepository->save($subevent);

        $role = new Role('role');
        $this->roleRepository->save($role);

        $block = new Block('block', 60, null, false, ProgramMandatoryType::VOLUNTARY);
        $block->setSubevent($subevent);
        $this->blockRepository->save($block);

        $programsCount = 10;
        $usersCount    = 400;
        $programs      = [];

        for ($i = 0; $i < $programsCount; $i++) {
            $program = new Program(new DateTimeImmutable('2020-01-01 08:00'));
            $program->setBlock($block);
            $this->programRepository->save($program);
            $programs[] = $program;
        }

        for ($i = 0; $i < $usersCount; $i++) {
            $user = new User();
            $user->setFirstName('First');
            $user->setLastName('Last');
            $user->addRole($role);
            $user->setApproved(true);
            $this->userRepository->save($user);

            ApplicationFactory::createRolesApplication($this->applicationRepository, $user, $role);
            ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user, $subevent);

            $this->programApplicationRepository->save(new ProgramApplication($user, $programs[rand(0, $programsCount - 1)]));
        }

        $time = microtime(true);

        $this->commandBus->handle(new RemoveBlock($block));

        $duration = microtime(true) - $time;

        $this->assertLessThan(30, $duration);
    }

    /** @return string[] */
    protected function getTestedAggregateRoots(): array
    {
        return [Block::class, Settings::class];
    }

    protected function _before(): void
    {
        $this->getModule('IntegrationTester')->useConfigFiles([__DIR__ . '/RemoveBlockHandlerPerformanceTest.neon']);

        parent::_before();

        $this->subeventRepository           = $this->getModule('IntegrationTester')->grabService(SubeventRepository::class);
        $this->userRepository               = $this->getModule('IntegrationTester')->grabService(UserRepository::class);
        $this->roleRepository               = $this->getModule('IntegrationTester')->grabService(RoleRepository::class);
        $this->programRepository            = $this->getModule('IntegrationTester')->grabService(ProgramRepository::class);
        $this->applicationRepository        = $this->getModule('IntegrationTester')->grabService(ApplicationRepository::class);
        $this->programApplicationRepository = $this->getModule('IntegrationTester')->grabService(ProgramApplicationRepository::class);
        $this->blockRepository              = $this->getModule('IntegrationTester')->grabService(BlockRepository::class);
        $this->settingsRepository           = $this->getModule('IntegrationTester')->grabService(SettingsRepository::class);

        $this->settingsRepository->save(new Settings(Settings::SEMINAR_NAME, 'test'));
    }
}
