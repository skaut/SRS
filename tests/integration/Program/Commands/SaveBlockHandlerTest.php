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
use App\Model\Program\Commands\SaveBlock;
use App\Model\Program\Exceptions\BlockCapacityInsufficientException;
use App\Model\Program\Program;
use App\Model\Program\ProgramApplication;
use App\Model\Program\Repositories\CategoryRepository;
use App\Model\Program\Repositories\ProgramApplicationRepository;
use App\Model\Program\Repositories\ProgramRepository;
use App\Model\Settings\Settings;
use App\Model\Structure\Repositories\SubeventRepository;
use App\Model\Structure\Subevent;
use App\Model\User\Repositories\UserRepository;
use App\Model\User\User;
use App\Services\ISettingsService;
use CommandHandlerTest;
use DateTimeImmutable;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Throwable;

final class SaveBlockHandlerTest extends CommandHandlerTest
{
    private ISettingsService $settingsService;

    private SubeventRepository $subeventRepository;

    private UserRepository $userRepository;

    private CategoryRepository $categoryRepository;

    private RoleRepository $roleRepository;

    private ProgramRepository $programRepository;

    private ApplicationRepository $applicationRepository;

    private ProgramApplicationRepository $programApplicationRepository;

    /**
     * Změna kategorie bloku - neoprávnění uživatelé jsou odhlášeni.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Throwable
     */
    public function testChangeCategory(): void
    {
        $subevent = new Subevent();
        $subevent->setName('subevent');
        $this->subeventRepository->save($subevent);

        $role1 = new Role('role1');
        $this->roleRepository->save($role1);

        $role2 = new Role('role2');
        $this->roleRepository->save($role2);

        $user1 = new User();
        $user1->setFirstName('First');
        $user1->setLastName('Last');
        $user1->addRole($role1);
        $user1->setApproved(true);
        $this->userRepository->save($user1);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user1, $role1);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user1, $subevent);

        $user2 = new User();
        $user2->setFirstName('First');
        $user2->setLastName('Last');
        $user2->addRole($role2);
        $user2->setApproved(true);
        $this->userRepository->save($user2);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user2, $role2);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user2, $subevent);

        $user3 = new User();
        $user3->setFirstName('First');
        $user3->setLastName('Last');
        $user3->addRole($role2);
        $user3->setApproved(true);
        $this->userRepository->save($user3);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user3, $role2);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user3, $subevent);

        $block = new Block('block', 60, 2, true, ProgramMandatoryType::VOLUNTARY);
        $block->setSubevent($subevent);
        $this->commandBus->handle(new SaveBlock($block));

        $program = new Program(new DateTimeImmutable('2020-01-01 08:00'));
        $program->setBlock($block);
        $this->programRepository->save($program);

        $programApplication1 = $this->programApplicationRepository->findByUserAndProgram($user1, $program);
        $this->assertNull($programApplication1);

        $programApplication2 = $this->programApplicationRepository->findByUserAndProgram($user2, $program);
        $this->assertNull($programApplication2);

        $programApplication3 = $this->programApplicationRepository->findByUserAndProgram($user3, $program);
        $this->assertNull($programApplication3);

        $this->programApplicationRepository->save(new ProgramApplication($user1, $program));
        $this->programApplicationRepository->save(new ProgramApplication($user2, $program));
        $this->programApplicationRepository->save(new ProgramApplication($user3, $program));

        $programApplication1 = $this->programApplicationRepository->findByUserAndProgram($user1, $program);
        $this->assertNotNull($programApplication1);
        $this->assertFalse($programApplication1->isAlternate());

        $programApplication2 = $this->programApplicationRepository->findByUserAndProgram($user2, $program);
        $this->assertNotNull($programApplication2);
        $this->assertFalse($programApplication2->isAlternate());

        $programApplication3 = $this->programApplicationRepository->findByUserAndProgram($user3, $program);
        $this->assertNotNull($programApplication3);
        $this->assertTrue($programApplication3->isAlternate());

        $category = new Category('category');
        $category->addRegisterableRole($role1);
        $this->categoryRepository->save($category);

        $blockOld = clone $block;
        $block->setCategory($category);
        $this->commandBus->handle(new SaveBlock($block, $blockOld));

        $programApplication1 = $this->programApplicationRepository->findByUserAndProgram($user1, $program);
        $this->assertNotNull($programApplication1);
        $this->assertFalse($programApplication1->isAlternate());

        $programApplication2 = $this->programApplicationRepository->findByUserAndProgram($user2, $program);
        $this->assertNull($programApplication2);

        $programApplication3 = $this->programApplicationRepository->findByUserAndProgram($user3, $program);
        $this->assertNull($programApplication3);
    }

    /**
     * Změna podakce automaticky zapisovaného bloku - neoprávnění uživatelé jsou odhlášeni, nově oprávnění přihlášeni.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Throwable
     */
    public function testChangeSubevent(): void
    {
        $subevent1 = new Subevent();
        $subevent1->setName('subevent1');
        $this->subeventRepository->save($subevent1);

        $subevent2 = new Subevent();
        $subevent2->setName('subevent2');
        $this->subeventRepository->save($subevent2);

        $role = new Role('role');
        $this->roleRepository->save($role);

        $user1 = new User();
        $user1->setFirstName('First');
        $user1->setLastName('Last');
        $user1->addRole($role);
        $user1->setApproved(true);
        $this->userRepository->save($user1);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user1, $role);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user1, $subevent1);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user1, $subevent2);

        $user2 = new User();
        $user2->setFirstName('First');
        $user2->setLastName('Last');
        $user2->addRole($role);
        $user2->setApproved(true);
        $this->userRepository->save($user2);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user2, $role);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user2, $subevent1);

        $user3 = new User();
        $user3->setFirstName('First');
        $user3->setLastName('Last');
        $user3->addRole($role);
        $user3->setApproved(true);
        $this->userRepository->save($user3);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user3, $role);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user3, $subevent2);

        $block = new Block('block', 60, null, true, ProgramMandatoryType::AUTO_REGISTERED);
        $block->setSubevent($subevent1);
        $this->commandBus->handle(new SaveBlock($block));

        $program = new Program(new DateTimeImmutable('2020-01-01 08:00'));
        $program->setBlock($block);
        $this->programRepository->save($program);

        $programApplication1 = $this->programApplicationRepository->findByUserAndProgram($user1, $program);
        $this->assertNull($programApplication1);

        $programApplication2 = $this->programApplicationRepository->findByUserAndProgram($user2, $program);
        $this->assertNull($programApplication2);

        $programApplication3 = $this->programApplicationRepository->findByUserAndProgram($user3, $program);
        $this->assertNull($programApplication3);

        $this->programApplicationRepository->save(new ProgramApplication($user1, $program));
        $this->programApplicationRepository->save(new ProgramApplication($user2, $program));

        $programApplication1 = $this->programApplicationRepository->findByUserAndProgram($user1, $program);
        $this->assertNotNull($programApplication1);

        $programApplication2 = $this->programApplicationRepository->findByUserAndProgram($user2, $program);
        $this->assertNotNull($programApplication2);

        $programApplication3 = $this->programApplicationRepository->findByUserAndProgram($user3, $program);
        $this->assertNull($programApplication3);

        $blockOld = clone $block;
        $block->setSubevent($subevent2);
        $this->commandBus->handle(new SaveBlock($block, $blockOld));

        $programApplication1 = $this->programApplicationRepository->findByUserAndProgram($user1, $program);
        $this->assertNotNull($programApplication1);

        $programApplication2 = $this->programApplicationRepository->findByUserAndProgram($user2, $program);
        $this->assertNull($programApplication2);

        $programApplication3 = $this->programApplicationRepository->findByUserAndProgram($user3, $program);
        $this->assertNotNull($programApplication3);
    }

    /**
     * Změna bloku na automaticky zapisovaný - oprávnění uživatelé jsou zapsáni.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function testChangeVoluntaryToAutoRegistered(): void
    {
        $subevent = new Subevent();
        $subevent->setName('subevent1');
        $this->subeventRepository->save($subevent);

        $role = new Role('role');
        $this->roleRepository->save($role);

        $user1 = new User();
        $user1->setFirstName('First');
        $user1->setLastName('Last');
        $user1->addRole($role);
        $user1->setApproved(true);
        $this->userRepository->save($user1);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user1, $role);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user1, $subevent);

        $user2 = new User();
        $user2->setFirstName('First');
        $user2->setLastName('Last');
        $user2->addRole($role);
        $user2->setApproved(true);
        $this->userRepository->save($user2);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user2, $role);

        $block = new Block('block', 60, null, true, ProgramMandatoryType::VOLUNTARY);
        $block->setSubevent($subevent);
        $this->commandBus->handle(new SaveBlock($block));

        $program = new Program(new DateTimeImmutable('2020-01-01 08:00'));
        $program->setBlock($block);
        $this->programRepository->save($program);

        $this->assertNull($this->programApplicationRepository->findByUserAndProgram($user1, $program));
        $this->assertNull($this->programApplicationRepository->findByUserAndProgram($user2, $program));

        $blockOld = clone $block;
        $block->setMandatory(ProgramMandatoryType::AUTO_REGISTERED);
        $this->commandBus->handle(new SaveBlock($block, $blockOld));

        $this->assertNotNull($this->programApplicationRepository->findByUserAndProgram($user1, $program));
        $this->assertNull($this->programApplicationRepository->findByUserAndProgram($user2, $program));
    }

    /**
     * Změna bloku z automaticky zapisovaného na povinný - uživatelé jsou odhlášeni.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Throwable
     */
    public function testChangeAutoRegisteredToMandatory(): void
    {
        $subevent = new Subevent();
        $subevent->setName('subevent1');
        $this->subeventRepository->save($subevent);

        $role = new Role('role');
        $this->roleRepository->save($role);

        $user1 = new User();
        $user1->setFirstName('First');
        $user1->setLastName('Last');
        $user1->addRole($role);
        $user1->setApproved(true);
        $this->userRepository->save($user1);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user1, $role);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user1, $subevent);

        $user2 = new User();
        $user2->setFirstName('First');
        $user2->setLastName('Last');
        $user2->addRole($role);
        $user2->setApproved(true);
        $this->userRepository->save($user2);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user2, $role);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user1, $subevent);

        $block = new Block('block', 60, null, true, ProgramMandatoryType::AUTO_REGISTERED);
        $block->setSubevent($subevent);
        $this->commandBus->handle(new SaveBlock($block));

        $program = new Program(new DateTimeImmutable('2020-01-01 08:00'));
        $program->setBlock($block);
        $this->programRepository->save($program);

        $this->programApplicationRepository->save(new ProgramApplication($user1, $program));
        $this->programApplicationRepository->save(new ProgramApplication($user2, $program));

        $this->assertNotNull($this->programApplicationRepository->findByUserAndProgram($user1, $program));
        $this->assertNotNull($this->programApplicationRepository->findByUserAndProgram($user2, $program));

        $blockOld = clone $block;
        $block->setMandatory(ProgramMandatoryType::MANDATORY);
        $this->commandBus->handle(new SaveBlock($block, $blockOld));

        $this->assertNull($this->programApplicationRepository->findByUserAndProgram($user1, $program));
        $this->assertNull($this->programApplicationRepository->findByUserAndProgram($user2, $program));
    }

    /**
     * Změna kapacity bloku - přihlášení náhradníků, nepovolení snížení pod počet účastníků.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Throwable
     */
    public function testChangeCapacity(): void
    {
        $subevent = new Subevent();
        $subevent->setName('subevent');
        $this->subeventRepository->save($subevent);

        $role = new Role('role1');
        $this->roleRepository->save($role);

        $user1 = new User();
        $user1->setFirstName('First');
        $user1->setLastName('Last');
        $user1->addRole($role);
        $user1->setApproved(true);
        $this->userRepository->save($user1);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user1, $role);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user1, $subevent);

        $user2 = new User();
        $user2->setFirstName('First');
        $user2->setLastName('Last');
        $user2->addRole($role);
        $user2->setApproved(true);
        $this->userRepository->save($user2);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user2, $role);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user2, $subevent);

        $user3 = new User();
        $user3->setFirstName('First');
        $user3->setLastName('Last');
        $user3->addRole($role);
        $user3->setApproved(true);
        $this->userRepository->save($user3);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user3, $role);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user3, $subevent);

        $user4 = new User();
        $user4->setFirstName('First');
        $user4->setLastName('Last');
        $user4->addRole($role);
        $user4->setApproved(true);
        $this->userRepository->save($user4);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user4, $role);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user4, $subevent);

        $user5 = new User();
        $user5->setFirstName('First');
        $user5->setLastName('Last');
        $user5->addRole($role);
        $user5->setApproved(true);
        $this->userRepository->save($user5);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user5, $role);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user5, $subevent);

        $block = new Block('block', 60, 1, true, ProgramMandatoryType::VOLUNTARY);
        $block->setSubevent($subevent);
        $this->commandBus->handle(new SaveBlock($block));

        $program1 = new Program(new DateTimeImmutable('2020-01-01 08:00'));
        $program1->setBlock($block);
        $this->programRepository->save($program1);

        $program2 = new Program(new DateTimeImmutable('2020-01-01 09:00'));
        $program2->setBlock($block);
        $this->programRepository->save($program2);

        $this->programApplicationRepository->save(new ProgramApplication($user1, $program1));
        $this->programApplicationRepository->save(new ProgramApplication($user3, $program1));
        $this->programApplicationRepository->save(new ProgramApplication($user4, $program1));

        $this->programApplicationRepository->save(new ProgramApplication($user2, $program2));
        $this->programApplicationRepository->save(new ProgramApplication($user4, $program2));
        $this->programApplicationRepository->save(new ProgramApplication($user3, $program2));
        $this->programApplicationRepository->save(new ProgramApplication($user5, $program2));

        $this->assertEquals(1, $program1->getAttendeesCount());
        $this->assertEquals(2, $program1->getAlternatesCount());
        $this->assertEquals(1, $program2->getAttendeesCount());
        $this->assertEquals(3, $program2->getAlternatesCount());

        $blockOld = clone $block;
        $block->setCapacity(2);
        $this->commandBus->handle(new SaveBlock($block, $blockOld));

        $this->assertEquals(2, $program1->getAttendeesCount());
        $this->assertEquals(0, $program1->getAlternatesCount());
        $this->assertEquals(2, $program2->getAttendeesCount());
        $this->assertEquals(1, $program2->getAlternatesCount());

        $blockOld = clone $block;
        $block->setCapacity(1);

        $this->expectException(BlockCapacityInsufficientException::class);
        try {
            $this->commandBus->handle(new SaveBlock($block, $blockOld));
        } catch (HandlerFailedException $e) {
            throw $e->getPrevious();
        }
    }

    /**
     * Zrušení povolení náhradníků.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Throwable
     */
    public function testChangeAlternatesAllowed(): void
    {
        $subevent = new Subevent();
        $subevent->setName('subevent');
        $this->subeventRepository->save($subevent);

        $role = new Role('role1');
        $this->roleRepository->save($role);

        $user1 = new User();
        $user1->setFirstName('First');
        $user1->setLastName('Last');
        $user1->addRole($role);
        $user1->setApproved(true);
        $this->userRepository->save($user1);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user1, $role);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user1, $subevent);

        $user2 = new User();
        $user2->setFirstName('First');
        $user2->setLastName('Last');
        $user2->addRole($role);
        $user2->setApproved(true);
        $this->userRepository->save($user2);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user2, $role);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user2, $subevent);

        $user3 = new User();
        $user3->setFirstName('First');
        $user3->setLastName('Last');
        $user3->addRole($role);
        $user3->setApproved(true);
        $this->userRepository->save($user3);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user3, $role);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user3, $subevent);

        $block = new Block('block', 60, 1, true, ProgramMandatoryType::VOLUNTARY);
        $block->setSubevent($subevent);
        $this->commandBus->handle(new SaveBlock($block));

        $program = new Program(new DateTimeImmutable('2020-01-01 08:00'));
        $program->setBlock($block);
        $this->programRepository->save($program);

        $this->programApplicationRepository->save(new ProgramApplication($user1, $program));
        $this->programApplicationRepository->save(new ProgramApplication($user2, $program));
        $this->programApplicationRepository->save(new ProgramApplication($user3, $program));

        $programApplication1 = $this->programApplicationRepository->findByUserAndProgram($user1, $program);
        $this->assertNotNull($programApplication1);
        $this->assertFalse($programApplication1->isAlternate());
        $programApplication2 = $this->programApplicationRepository->findByUserAndProgram($user2, $program);
        $this->assertNotNull($programApplication2);
        $this->assertTrue($programApplication2->isAlternate());
        $programApplication3 = $this->programApplicationRepository->findByUserAndProgram($user3, $program);
        $this->assertNotNull($programApplication3);
        $this->assertTrue($programApplication3->isAlternate());

        $blockOld = clone $block;
        $block->setAlternatesAllowed(false);
        $this->commandBus->handle(new SaveBlock($block, $blockOld));

        $programApplication1 = $this->programApplicationRepository->findByUserAndProgram($user1, $program);
        $this->assertNotNull($programApplication1);
        $this->assertFalse($programApplication1->isAlternate());
        $programApplication2 = $this->programApplicationRepository->findByUserAndProgram($user2, $program);
        $this->assertNull($programApplication2);
        $programApplication3 = $this->programApplicationRepository->findByUserAndProgram($user3, $program);
        $this->assertNull($programApplication3);

        $blockOld = clone $block;
        $block->setCapacity(1);
    }

    /**
     * @return string[]
     */
    protected function getTestedAggregateRoots(): array
    {
        return [Block::class];
    }

    protected function _before(): void
    {
        $this->tester->useConfigFiles([__DIR__ . '/SaveBlockHandlerTest.neon']);
        parent::_before();

        $this->settingsService              = $this->tester->grabService(ISettingsService::class);
        $this->subeventRepository           = $this->tester->grabService(SubeventRepository::class);
        $this->userRepository               = $this->tester->grabService(UserRepository::class);
        $this->categoryRepository           = $this->tester->grabService(CategoryRepository::class);
        $this->roleRepository               = $this->tester->grabService(RoleRepository::class);
        $this->programRepository            = $this->tester->grabService(ProgramRepository::class);
        $this->applicationRepository        = $this->tester->grabService(ApplicationRepository::class);
        $this->programApplicationRepository = $this->tester->grabService(ProgramApplicationRepository::class);

        $this->settingsService->setBoolValue(Settings::IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT, false);
        $this->settingsService->setValue(Settings::SEMINAR_NAME, 'test');
    }
}
