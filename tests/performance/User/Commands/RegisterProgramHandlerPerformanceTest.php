<?php

declare(strict_types=1);

namespace App\Model\User\Commands\Handlers;

use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Acl\Role;
use App\Model\Application\ApplicationFactory;
use App\Model\Application\Repositories\ApplicationRepository;
use App\Model\Enums\ProgramMandatoryType;
use App\Model\Program\Block;
use App\Model\Program\Program;
use App\Model\Program\Repositories\BlockRepository;
use App\Model\Program\Repositories\ProgramRepository;
use App\Model\Settings\Repositories\SettingsRepository;
use App\Model\Settings\Settings;
use App\Model\Structure\Repositories\SubeventRepository;
use App\Model\Structure\Subevent;
use App\Model\User\Commands\RegisterProgram;
use App\Model\User\Repositories\UserRepository;
use App\Model\User\User;
use CommandHandlerTest;
use DateTimeImmutable;

use function microtime;

final class RegisterProgramHandlerPerformanceTest extends CommandHandlerTest
{
    private BlockRepository $blockRepository;

    private SubeventRepository $subeventRepository;

    private UserRepository $userRepository;

    private RoleRepository $roleRepository;

    private ProgramRepository $programRepository;

    private ApplicationRepository $applicationRepository;

    private SettingsRepository $settingsRepository;

    /**
     * Hromadné přihlášení velkého množství účastníků.
     */
    public function testRegisterMultipleUsers(): void
    {
        $subevent = new Subevent();
        $subevent->setName('subevent');
        $this->subeventRepository->save($subevent);

        $role = new Role('role');
        $this->roleRepository->save($role);

        $block = new Block('block', 60, null, false, ProgramMandatoryType::VOLUNTARY);
        $block->setSubevent($subevent);
        $this->blockRepository->save($block);

        $program = new Program(new DateTimeImmutable('2020-01-01 08:00'));
        $program->setBlock($block);
        $this->programRepository->save($program);

        $usersCount = 400;
        $users      = [];

        for ($i = 0; $i < $usersCount; $i++) {
            $user = new User();
            $user->setFirstName('First');
            $user->setLastName('Last');
            $user->addRole($role);
            $user->setApproved(true);
            $this->userRepository->save($user);
            $users[] = $user;

            ApplicationFactory::createRolesApplication($this->applicationRepository, $user, $role);
            ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user, $subevent);
        }

        $time = microtime(true);

        for ($i = 0; $i < $usersCount; $i++) {
            $this->commandBus->handle(new RegisterProgram($users[$i], $program, false));
        }

        $duration = microtime(true) - $time;

        $this->assertLessThan(30, $duration);
    }

    /** @return string[] */
    protected function getTestedAggregateRoots(): array
    {
        return [User::class, Settings::class];
    }

    protected function _before(): void
    {
        $this->tester->useConfigFiles([__DIR__ . '/RegisterProgramHandlerPerformanceTest.neon']);

        parent::_before();

        $this->blockRepository       = $this->tester->grabService(BlockRepository::class);
        $this->subeventRepository    = $this->tester->grabService(SubeventRepository::class);
        $this->userRepository        = $this->tester->grabService(UserRepository::class);
        $this->roleRepository        = $this->tester->grabService(RoleRepository::class);
        $this->programRepository     = $this->tester->grabService(ProgramRepository::class);
        $this->applicationRepository = $this->tester->grabService(ApplicationRepository::class);
        $this->settingsRepository    = $this->tester->grabService(SettingsRepository::class);

        $this->settingsRepository->save(new Settings(Settings::IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT, (string) false));
    }
}
