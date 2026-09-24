<?php

declare(strict_types=1);

namespace App\ApiModule\Services;

use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Acl\Role;
use App\Model\Application\ApplicationFactory;
use App\Model\Application\Repositories\ApplicationRepository;
use App\Model\Enums\ProgramMandatoryType;
use App\Model\Program\Block;
use App\Model\Program\Repositories\BlockRepository;
use App\Model\Settings\Repositories\SettingsRepository;
use App\Model\Settings\Settings;
use App\Model\Structure\Repositories\SubeventRepository;
use App\Model\Structure\Subevent;
use App\Model\User\Repositories\UserRepository;
use App\Model\User\User;
use Doctrine\DBAL\Logging\DebugStack;
use IntegrationTest;

use function array_filter;
use function count;
use function preg_match;

final class ScheduleServiceTest extends IntegrationTest
{
    private ScheduleService $scheduleService;

    private BlockRepository $blockRepository;

    private SubeventRepository $subeventRepository;

    private UserRepository $userRepository;

    private RoleRepository $roleRepository;

    private ApplicationRepository $applicationRepository;

    private SettingsRepository $settingsRepository;

    private Subevent $subevent;

    private Role $role;

    private int $blocksCreated = 0;

    /**
     * Počet dotazů na oprávněné bloky nezávisí na počtu bloků.
     *
     * Dotaz UserAllowedBlocksQuery se dříve prováděl pro každý blok zvlášť, výpis se tak
     * s rostoucím počtem bloků (a účastníků) neúnosně zpomaloval.
     */
    public function testGetBlocksDoesNotQueryPerBlock(): void
    {
        $user = $this->createUser();
        $this->scheduleService->setUser($user->getId());

        $this->createBlocks(5);
        $this->assertSame(1, $this->countAllowedBlocksQueries(), 'Bloky: 5');

        $this->createBlocks(45);
        $this->assertSame(1, $this->countAllowedBlocksQueries(), 'Bloky: 50');
    }

    /**
     * Příznaky user_allowed a user_attends odpovídají oprávněním uživatele.
     */
    public function testGetBlocksSetsUserFlags(): void
    {
        $allowedUser = $this->createUser();

        // uživatel bez přihlášky na podakci nemá na blok nárok
        $otherUser = new User();
        $otherUser->setFirstName('Other');
        $otherUser->setLastName('User');
        $otherUser->addRole($this->role);
        $otherUser->setApproved(true);
        $this->userRepository->save($otherUser);

        $this->createBlocks(3);

        $this->scheduleService->setUser($allowedUser->getId());
        foreach ($this->scheduleService->getBlocks() as $blockDetailDto) {
            $this->assertTrue($blockDetailDto->isUserAllowed());
            $this->assertFalse($blockDetailDto->isUserAttends());
        }

        $this->scheduleService->setUser($otherUser->getId());
        foreach ($this->scheduleService->getBlocks() as $blockDetailDto) {
            $this->assertFalse($blockDetailDto->isUserAllowed());
        }
    }

    /** @return string[] */
    protected function getTestedAggregateRoots(): array
    {
        return [User::class, Settings::class];
    }

    protected function _before(): void
    {
        $this->tester->useConfigFiles([__DIR__ . '/ScheduleServiceTest.neon']);

        parent::_before();

        $this->scheduleService       = $this->tester->grabService(ScheduleService::class);
        $this->blockRepository       = $this->tester->grabService(BlockRepository::class);
        $this->subeventRepository    = $this->tester->grabService(SubeventRepository::class);
        $this->userRepository        = $this->tester->grabService(UserRepository::class);
        $this->roleRepository        = $this->tester->grabService(RoleRepository::class);
        $this->applicationRepository = $this->tester->grabService(ApplicationRepository::class);
        $this->settingsRepository    = $this->tester->grabService(SettingsRepository::class);

        $this->settingsRepository->save(new Settings(Settings::IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT, (string) false));

        $this->subevent = new Subevent();
        $this->subevent->setName('subevent');
        $this->subeventRepository->save($this->subevent);

        $this->role = new Role('role');
        $this->roleRepository->save($this->role);
    }

    private function createUser(): User
    {
        $user = new User();
        $user->setFirstName('First');
        $user->setLastName('Last');
        $user->addRole($this->role);
        $user->setApproved(true);
        $this->userRepository->save($user);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user, $this->role);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user, $this->subevent);

        return $user;
    }

    private function createBlocks(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            // název bloku musí být unikátní
            $block = new Block('block' . $this->blocksCreated++, 60, null, false, ProgramMandatoryType::VOLUNTARY);
            $block->setSubevent($this->subevent);
            // BlockDetailDto nepřijímá null
            $block->setPerex('perex');
            $block->setDescription('description');
            $this->blockRepository->save($block);
        }
    }

    /**
     * Zavolá getBlocks() a vrátí počet provedených dotazů nad tabulkou application.
     *
     * Tabulku application používá v tomto průchodu jen UserAllowedBlocksQuery - na rozdíl
     * od program_application, přes kterou se hledají bloky, na které je uživatel zapsaný.
     */
    private function countAllowedBlocksQueries(): int
    {
        $logger = new DebugStack();
        $this->em->getConnection()->getConfiguration()->setSQLLogger($logger);

        $this->scheduleService->getBlocks();

        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        return count(array_filter(
            $logger->queries,
            static fn (array $query) => preg_match('/\bapplication\b/', $query['sql']) === 1,
        ));
    }
}
