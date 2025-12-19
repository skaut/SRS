<?php

declare(strict_types=1);

namespace App\Model\User\Commands\Handlers;

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
use App\Model\Program\Exceptions\UserNotAttendsProgramException;
use App\Model\Program\Program;
use App\Model\Program\ProgramApplication;
use App\Model\Program\Repositories\BlockRepository;
use App\Model\Program\Repositories\ProgramApplicationRepository;
use App\Model\Program\Repositories\ProgramRepository;
use App\Model\Settings\Repositories\SettingsRepository;
use App\Model\Settings\Settings;
use App\Model\Structure\Repositories\SubeventRepository;
use App\Model\Structure\Subevent;
use App\Model\User\Commands\UnregisterProgram;
use App\Model\User\Repositories\UserRepository;
use App\Model\User\User;
use CommandHandlerTest;
use DateTimeImmutable;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Throwable;

final class UnregisterProgramHandlerTest extends CommandHandlerTest
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
     * Odhlášení uživatelé jsou nahrazeni prvními náhradníky, přihlášení jako náhradník se registrací na program ruší.
     *
     * @throws OptimisticLockException
     * @throws Throwable
     */
    public function testReplaceByAlternates(): void
    {
        $subevent = new Subevent();
        $subevent->setName('subevent');
        $this->subeventRepository->save($subevent);

        $block = new Block('block', 60, 1, true, ProgramMandatoryType::VOLUNTARY);
        $block->setSubevent($subevent);
        $this->blockRepository->save($block);

        $program1 = new Program(new DateTimeImmutable('2020-01-01 08:00'));
        $program1->setBlock($block);
        $this->programRepository->save($program1);

        $program2 = new Program(new DateTimeImmutable('2020-01-01 10:00'));
        $program2->setBlock($block);
        $this->programRepository->save($program2);

        $role = new Role('role');
        $this->roleRepository->save($role);

        $user1 = new User();
        $user1->setFirstName('First');
        $user1->setLastName('Last');
        $user1->setEmail('mail@mail.cz');
        $user1->addRole($role);
        $user1->setApproved(true);
        $this->userRepository->save($user1);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user1, $role);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user1, $subevent);
        $this->programApplicationRepository->save(new ProgramApplication($user1, $program1));

        $user2 = new User();
        $user2->setFirstName('First');
        $user2->setLastName('Last');
        $user2->setEmail('mail@mail.cz');
        $user2->addRole($role);
        $user2->setApproved(true);
        $this->userRepository->save($user2);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user2, $role);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user2, $subevent);
        $this->programApplicationRepository->save(new ProgramApplication($user2, $program2));

        $user3 = new User();
        $user3->setFirstName('First');
        $user3->setLastName('Last');
        $user3->setEmail('mail@mail.cz');
        $user3->addRole($role);
        $user3->setApproved(true);
        $this->userRepository->save($user3);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user3, $role);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user3, $subevent);
        $this->programApplicationRepository->save(new ProgramApplication($user3, $program1));
        $this->programApplicationRepository->save(new ProgramApplication($user3, $program2));

        $user4 = new User();
        $user4->setFirstName('First');
        $user4->setLastName('Last');
        $user4->setEmail('mail@mail.cz');
        $user4->addRole($role);
        $user4->setApproved(true);
        $this->userRepository->save($user4);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user4, $role);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user4, $subevent);
        $this->programApplicationRepository->save(new ProgramApplication($user4, $program1));
        $this->programApplicationRepository->save(new ProgramApplication($user4, $program2));

        $this->assertEquals(1, $program1->getAttendeesCount());
        $this->assertEquals(2, $program1->getAlternatesCount());
        $this->assertEquals(1, $program2->getAttendeesCount());
        $this->assertEquals(2, $program2->getAlternatesCount());

        // odhlášení uživatele 1 z 1. programu, uživatel 3 se stává účastníkem a už není prvním náhradníkem programu 2
        $this->commandBus->handle(new UnregisterProgram($user1, $program1, false));

        $programApplication11 = $this->programApplicationRepository->findByUserAndProgram($user1, $program1);
        $this->assertNull($programApplication11);

        $programApplication31 = $this->programApplicationRepository->findByUserAndProgram($user3, $program1);
        $this->assertEquals($user3, $programApplication31->getUser());
        $this->assertEquals($program1, $programApplication31->getProgram());
        $this->assertFalse($programApplication31->isAlternate());

        $programApplication32 = $this->programApplicationRepository->findByUserAndProgram($user3, $program2);
        $this->assertNull($programApplication32);

        $programApplication41 = $this->programApplicationRepository->findByUserAndProgram($user4, $program1);
        $this->assertEquals($user4, $programApplication41->getUser());
        $this->assertEquals($program1, $programApplication41->getProgram());
        $this->assertTrue($programApplication41->isAlternate());

        $programApplication42 = $this->programApplicationRepository->findByUserAndProgram($user4, $program2);
        $this->assertEquals($user4, $programApplication42->getUser());
        $this->assertEquals($program2, $programApplication42->getProgram());
        $this->assertTrue($programApplication42->isAlternate());

        $this->assertEquals(1, $program1->getAttendeesCount());
        $this->assertEquals(1, $program1->getAlternatesCount());
        $this->assertEquals(1, $program2->getAttendeesCount());
        $this->assertEquals(1, $program2->getAlternatesCount());

        // odhlášení uživatele 2 z 2. programu, uživatel 4 se stává účastníkem
        $this->commandBus->handle(new UnregisterProgram($user2, $program2, false));

        $programApplication22 = $this->programApplicationRepository->findByUserAndProgram($user2, $program2);
        $this->assertNull($programApplication22);

        $programApplication31 = $this->programApplicationRepository->findByUserAndProgram($user3, $program1);
        $this->assertEquals($user3, $programApplication31->getUser());
        $this->assertEquals($program1, $programApplication31->getProgram());
        $this->assertFalse($programApplication31->isAlternate());

        $programApplication32 = $this->programApplicationRepository->findByUserAndProgram($user3, $program2);
        $this->assertNull($programApplication32);

        $programApplication41 = $this->programApplicationRepository->findByUserAndProgram($user4, $program1);
        $this->assertNull($programApplication41);

        $programApplication42 = $this->programApplicationRepository->findByUserAndProgram($user4, $program2);
        $this->assertEquals($user4, $programApplication42->getUser());
        $this->assertEquals($program2, $programApplication42->getProgram());
        $this->assertFalse($programApplication42->isAlternate());

        $this->assertEquals(1, $program1->getAttendeesCount());
        $this->assertEquals(0, $program1->getAlternatesCount());
        $this->assertEquals(1, $program2->getAttendeesCount());
        $this->assertEquals(0, $program2->getAlternatesCount());
    }

    /**
     * Uživatel není účastníkem programu.
     *
     * @throws OptimisticLockException
     * @throws Throwable
     */
    public function testNotAttends(): void
    {
        $subevent = new Subevent();
        $subevent->setName('subevent');
        $this->subeventRepository->save($subevent);

        $block = new Block('block', 60, 1, false, ProgramMandatoryType::VOLUNTARY);
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
        $user->setApproved(true);
        $this->userRepository->save($user);

        ApplicationFactory::createRolesApplication($this->applicationRepository, $user, $role);
        ApplicationFactory::createSubeventsApplication($this->applicationRepository, $user, $subevent);

        $this->expectException(UserNotAttendsProgramException::class);
        try {
            $this->commandBus->handle(new UnregisterProgram($user, $program, false));
        } catch (HandlerFailedException $e) {
            throw $e->getPrevious();
        }
    }

    /** @return string[] */
    protected function getTestedAggregateRoots(): array
    {
        return [User::class, Settings::class, Mail::class, MailQueue::class, Template::class];
    }

    protected function _before(): void
    {
        $this->getModule('IntegrationTester')->useConfigFiles([__DIR__ . '/UnregisterProgramHandlerTest.neon']);

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
    }
}
