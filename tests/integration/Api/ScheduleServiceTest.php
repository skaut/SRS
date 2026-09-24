<?php

declare(strict_types=1);

namespace App\ApiModule\Services;

use App\ApiModule\Dto\Schedule\ProgramDetailDto;
use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Acl\Role;
use App\Model\Application\ApplicationFactory;
use App\Model\Application\Repositories\ApplicationRepository;
use App\Model\Enums\ProgramMandatoryType;
use App\Model\Program\Block;
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
use DateTimeImmutable;
use Doctrine\DBAL\Logging\DebugStack;
use IntegrationTest;

use function array_filter;
use function count;
use function preg_match;

final class ScheduleServiceTest extends IntegrationTest
{
    private ScheduleService $scheduleService;

    private BlockRepository $blockRepository;

    private ProgramRepository $programRepository;

    private ProgramApplicationRepository $programApplicationRepository;

    private SubeventRepository $subeventRepository;

    private UserRepository $userRepository;

    private RoleRepository $roleRepository;

    private ApplicationRepository $applicationRepository;

    private SettingsRepository $settingsRepository;

    private Subevent $subevent;

    private Role $role;

    private int $blocksCreated = 0;

    private int $programsCreated = 0;

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

    /**
     * Počet dotazů na přihlášky programů nezávisí na počtu programů.
     *
     * Dřív se pro každý program zvlášť načítali účastníci i náhradníci, navíc jako celé
     * entity uživatelů.
     */
    public function testGetProgramsWebDoesNotQueryPerProgram(): void
    {
        $user = $this->createUser();
        $this->scheduleService->setUser($user->getId());

        $this->createProgramsInOwnBlocks(3);
        $this->assertSame(2, $this->countProgramApplicationQueries(), 'Programy: 3');

        $this->createProgramsInOwnBlocks(27);
        $this->assertSame(2, $this->countProgramApplicationQueries(), 'Programy: 30');
    }

    /**
     * Překryvy, programy stejného bloku a počty účastníků se počítají správně.
     */
    public function testGetProgramsWebRelatedProgramsAndCounts(): void
    {
        $user      = $this->createUser();
        $otherUser = $this->createUser();

        $block      = $this->createBlock(60);
        $otherBlock = $this->createBlock(60);

        // A a B jsou ve stejném bloku a nepřekrývají se, C se překrývá jen s A
        $programA = $this->createProgram($block, '2020-01-01 08:00');
        $programB = $this->createProgram($block, '2020-01-01 10:00');
        $programC = $this->createProgram($otherBlock, '2020-01-01 08:30');

        $this->programApplicationRepository->save(new ProgramApplication($otherUser, $programA));

        $this->scheduleService->setUser($user->getId());
        $dtos = $this->indexById($this->scheduleService->getProgramsWeb());

        $this->assertSame([$programB->getId()], $dtos[$programA->getId()]->getSameBlockPrograms());
        $this->assertSame([$programA->getId()], $dtos[$programB->getId()]->getSameBlockPrograms());
        $this->assertSame([], $dtos[$programC->getId()]->getSameBlockPrograms());

        $this->assertSame([$programC->getId()], $dtos[$programA->getId()]->getOverlappingPrograms());
        $this->assertSame([], $dtos[$programB->getId()]->getOverlappingPrograms());
        $this->assertSame([$programA->getId()], $dtos[$programC->getId()]->getOverlappingPrograms());

        $this->assertSame(1, $dtos[$programA->getId()]->getAttendeesCount());
        $this->assertSame(0, $dtos[$programA->getId()]->getAlternatesCount());
        $this->assertSame(0, $dtos[$programB->getId()]->getAttendeesCount());

        foreach ($dtos as $dto) {
            $this->assertFalse($dto->getUserAttends());
            $this->assertFalse($dto->getUserAlternates());
            $this->assertFalse($dto->isBlocked());
        }
    }

    /**
     * Po přihlášení na program se blokují programy stejného bloku i překrývající se programy.
     */
    public function testGetProgramsWebBlocksRelatedProgramsWhenUserAttends(): void
    {
        $user = $this->createUser();

        $block      = $this->createBlock(60);
        $otherBlock = $this->createBlock(60);

        $programA = $this->createProgram($block, '2020-01-01 08:00');
        $programB = $this->createProgram($block, '2020-01-01 10:00');
        $programC = $this->createProgram($otherBlock, '2020-01-01 08:30');

        $this->programApplicationRepository->save(new ProgramApplication($user, $programA));

        $this->scheduleService->setUser($user->getId());
        $dtos = $this->indexById($this->scheduleService->getProgramsWeb());

        $this->assertTrue($dtos[$programA->getId()]->getUserAttends());
        $this->assertSame(1, $dtos[$programA->getId()]->getAttendeesCount());
        $this->assertFalse($dtos[$programA->getId()]->isBlocked());

        // stejný blok jako A
        $this->assertTrue($dtos[$programB->getId()]->isBlocked());
        // překrývá se s A
        $this->assertTrue($dtos[$programC->getId()]->isBlocked());
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

        $this->scheduleService   = $this->tester->grabService(ScheduleService::class);
        $this->blockRepository   = $this->tester->grabService(BlockRepository::class);
        $this->programRepository = $this->tester->grabService(ProgramRepository::class);

        $this->programApplicationRepository = $this->tester->grabService(ProgramApplicationRepository::class);
        $this->subeventRepository           = $this->tester->grabService(SubeventRepository::class);
        $this->userRepository               = $this->tester->grabService(UserRepository::class);
        $this->roleRepository               = $this->tester->grabService(RoleRepository::class);
        $this->applicationRepository        = $this->tester->grabService(ApplicationRepository::class);
        $this->settingsRepository           = $this->tester->grabService(SettingsRepository::class);

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

    private function createBlock(int $duration): Block
    {
        // název bloku musí být unikátní
        $block = new Block('block' . $this->blocksCreated++, $duration, null, false, ProgramMandatoryType::VOLUNTARY);
        $block->setSubevent($this->subevent);
        // BlockDetailDto nepřijímá null
        $block->setPerex('perex');
        $block->setDescription('description');
        $this->blockRepository->save($block);

        return $block;
    }

    private function createProgram(Block $block, string $start): Program
    {
        $program = new Program(new DateTimeImmutable($start));
        $program->setBlock($block);
        $this->programRepository->save($program);

        return $program;
    }

    /**
     * Vytvoří programy, každý ve vlastním bloku a v jiném čase, aby se navzájem neblokovaly.
     */
    private function createProgramsInOwnBlocks(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $this->createProgram(
                $this->createBlock(60),
                '2020-01-01 00:00 +' . $this->programsCreated++ . ' hours',
            );
        }
    }

    private function countProgramApplicationQueries(): int
    {
        return $this->countQueriesMatching(
            '/\bprogram_application\b/',
            fn () => $this->scheduleService->getProgramsWeb(),
        );
    }

    /**
     * @param ProgramDetailDto[] $dtos
     *
     * @return array<int, ProgramDetailDto>
     */
    private function indexById(array $dtos): array
    {
        $indexed = [];
        foreach ($dtos as $dto) {
            $indexed[$dto->getId()] = $dto;
        }

        return $indexed;
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
        return $this->countQueriesMatching(
            '/\bapplication\b/',
            fn () => $this->scheduleService->getBlocks(),
        );
    }

    /**
     * Vrátí počet dotazů provedených během callbacku, jejichž SQL odpovídá vzoru.
     */
    private function countQueriesMatching(string $pattern, callable $callback): int
    {
        $logger = new DebugStack();
        $this->em->getConnection()->getConfiguration()->setSQLLogger($logger);

        $callback();

        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        return count(array_filter(
            $logger->queries,
            static fn (array $query) => preg_match($pattern, $query['sql']) === 1,
        ));
    }
}
