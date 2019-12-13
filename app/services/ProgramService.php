<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Model\ACL\Role;
use App\Model\Enums\ProgramMandatoryType;
use App\Model\Enums\ProgramRegistrationType;
use App\Model\Mailing\Template;
use App\Model\Mailing\TemplateVariable;
use App\Model\Program\Block;
use App\Model\Program\BlockRepository;
use App\Model\Program\Category;
use App\Model\Program\CategoryRepository;
use App\Model\Program\Program;
use App\Model\Program\ProgramRepository;
use App\Model\Program\Room;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Model\Settings\SettingsRepository;
use App\Model\Structure\Subevent;
use App\Model\User\User;
use App\Model\User\UserRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Nette;
use Nettrine\ORM\EntityManagerDecorator;
use Throwable;
use Ublaboo\Mailing\Exception\MailingException;
use Ublaboo\Mailing\Exception\MailingMailCreationException;

/**
 * Služba pro správu programů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class ProgramService
{
    use Nette\SmartObject;

    /** @var EntityManagerDecorator */
    private $em;

    /** @var SettingsService */
    private $settingsService;

    /** @var ProgramRepository */
    private $programRepository;

    /** @var BlockRepository */
    private $blockRepository;

    /** @var UserRepository */
    private $userRepository;

    /** @var CategoryRepository */
    private $categoryRepository;

    /** @var MailService */
    private $mailService;


    public function __construct(
        EntityManagerDecorator $em,
        SettingsService $settingsService,
        ProgramRepository $programRepository,
        BlockRepository $blockRepository,
        UserRepository $userRepository,
        CategoryRepository $categoryRepository,
        MailService $mailService
    ) {
        $this->em                 = $em;
        $this->settingsService    = $settingsService;
        $this->programRepository  = $programRepository;
        $this->blockRepository    = $blockRepository;
        $this->userRepository     = $userRepository;
        $this->categoryRepository = $categoryRepository;
        $this->mailService        = $mailService;
    }

    /**
     * Vytvoří programový blok.
     * @param Collection|User[] $lectors
     * @throws Throwable
     */
    public function createBlock(
        string $name,
        Subevent $subevent,
        ?Category $category,
        Collection $lectors,
        int $duration,
        ?int $capacity,
        string $mandatory,
        string $perex,
        string $description,
        string $tools
    ) : void {
        $this->em->transactional(function () use ($name, $subevent, $category, $lectors, $duration, $capacity, $mandatory, $perex, $description, $tools) : void {
            $block = new Block();

            $block->setName($name);
            $block->setSubevent($subevent);
            $block->setCategory($category);
            $block->setLectors($lectors);
            $block->setDuration($duration);
            $block->setCapacity($capacity);
            $block->setMandatory($mandatory);
            $block->setPerex($perex);
            $block->setDescription($description);
            $block->setTools($tools);

            $this->blockRepository->save($block);

//            $this->updateUsersNotRegisteredMandatoryBlocks(new ArrayCollection($this->userRepository->findAll())); TODO: optimalizovat
        });
    }

    /**
     * Aktualizuje programový blok.
     * @param Collection|User[] $lectors
     * @throws Throwable
     */
    public function updateBlock(
        Block $block,
        string $name,
        Subevent $subevent,
        ?Category $category,
        Collection $lectors,
        int $duration,
        ?int $capacity,
        string $mandatory,
        string $perex,
        string $description,
        string $tools
    ) : void {
        $this->em->transactional(function () use ($block, $name, $subevent, $category, $lectors, $duration, $capacity, $mandatory, $perex, $description, $tools) : void {
            $oldSubevent = $block->getSubevent();
            $oldCategory = $block->getCategory();

//            $oldAllowedUsers = clone $this->userRepository->findBlockAllowed($block);

            $block->setName($name);
            $block->setSubevent($subevent);
            $block->setCategory($category);
            $block->setLectors($lectors);
            $block->setDuration($duration);
            $block->setCapacity($capacity);
            $block->setPerex($perex);
            $block->setDescription($description);
            $block->setTools($tools);

            $this->blockRepository->save($block);

            //aktualizace ucastniku pri zmene kategorie nebo podakce
            if (($category !== $oldCategory) || ($subevent !== $oldSubevent)) {
                $allowedUsers = $this->userRepository->findBlockAllowed($block);

                foreach ($block->getPrograms() as $program) {
                    foreach ($program->getAttendees() as $user) {
                        if ($allowedUsers->contains($user)) {
                            continue;
                        }

                        $this->unregisterProgramImpl($user, $program);
                    }

                    if ($mandatory !== ProgramMandatoryType::AUTO_REGISTERED) {
                        continue;
                    }

                    foreach ($allowedUsers as $user) {
                        $this->registerProgramImpl($user, $program);
                    }
                }

//                $this->updateUsersNotRegisteredMandatoryBlocks($oldAllowedUsers); TODO: optimalizovat
//                $this->updateUsersNotRegisteredMandatoryBlocks($allowedUsers); TODO: optimalizovat
            }

            //aktualizace ucastniku pri zmene povinnosti
            $this->updateBlockMandatoryImpl($block, $mandatory);
        });
    }

    /**
     * Aktualizuje povinnost bloku.
     * @throws Throwable
     */
    public function updateBlockMandatory(Block $block, string $mandatory) : void
    {
        $this->em->transactional(function () use ($block, $mandatory) : void {
            $this->updateBlockMandatoryImpl($block, $mandatory);
        });
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws SettingsException
     * @throws Throwable
     * @throws MailingException
     * @throws MailingMailCreationException
     */
    private function updateBlockMandatoryImpl(Block $block, string $mandatory) : void
    {
        $oldMandatory = $block->getMandatory();

        if ($mandatory === $oldMandatory) {
            return;
        }

        $block->setMandatory($mandatory);

        $this->blockRepository->save($block);

        //odstraneni ucastniku, pokud se odstrani automaticke prihlasovani
        if ($oldMandatory === ProgramMandatoryType::AUTO_REGISTERED && $mandatory !== ProgramMandatoryType::AUTO_REGISTERED) {
            foreach ($block->getPrograms() as $program) {
                foreach ($program->getAttendees() as $user) {
                    $this->unregisterProgramImpl($user, $program);
                }
            }
        }

        //pridani ucastniku, pokud je pridano automaticke prihlaseni
        if ($oldMandatory !== ProgramMandatoryType::AUTO_REGISTERED && $mandatory === ProgramMandatoryType::AUTO_REGISTERED) {
            $allowedUsers = $this->userRepository->findBlockAllowed($block);

            foreach ($block->getPrograms() as $program) {
                foreach ($allowedUsers as $user) {
                    $this->registerProgramImpl($user, $program);
                }
            }
        }

        //prepocet neprihlasenych povinnych bloku, pri zmene z povinneho na nepovinny a naopak
//        if (($oldMandatory === ProgramMandatoryType::VOLUNTARY &&
//                ($mandatory === ProgramMandatoryType::MANDATORY || $mandatory === ProgramMandatoryType::AUTO_REGISTERED))
//            || (($oldMandatory === ProgramMandatoryType::MANDATORY || $oldMandatory === ProgramMandatoryType::AUTO_REGISTERED)
//                && $mandatory === ProgramMandatoryType::VOLUNTARY)) {
//            $this->updateUsersNotRegisteredMandatoryBlocks($this->userRepository->findBlockAllowed($block));
//        } TODO: optimalizovat

        $this->blockRepository->save($block);
    }

    /**
     * Odstraní programový blok.
     * @throws Throwable
     */
    public function removeBlock(Block $block) : void
    {
        $this->em->transactional(function () use ($block) : void {
            $isVoluntary = $block->getMandatory() === ProgramMandatoryType::VOLUNTARY;

            $this->blockRepository->remove($block);

            if ($isVoluntary) {
                return;
            }

//            $this->updateUsersNotRegisteredMandatoryBlocks(new ArrayCollection($this->userRepository->findAll())); TODO: optimalizovat
        });
    }

    /**
     * Vytvoří kategorii programů.
     * @param Collection|Role[] $registerableRoles
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function createCategory(string $name, Collection $registerableRoles) : void
    {
        $category = new Category();

        $category->setName($name);
        $category->setRegisterableRoles($registerableRoles);

        $this->categoryRepository->save($category);
    }

    /**
     * Aktualizuje kategorii programů.
     * @param Collection|Role[] $registerableRoles
     * @throws Throwable
     */
    public function updateCategory(Category $category, string $name, Collection $registerableRoles) : void
    {
        $this->em->transactional(function () use ($category, $name, $registerableRoles) : void {
            $category->setName($name);
            $category->setRegisterableRoles($registerableRoles);

            $this->categoryRepository->save($category);

            $this->updateUsersPrograms(new ArrayCollection($this->userRepository->findAll()));
        });
    }

    /**
     * Vytvoří program v harmonogramu.
     * @throws Throwable
     */
    public function createProgram(Block $block, ?Room $room, DateTime $start) : Program
    {
        $program = new Program($block);

        $program->setRoom($room);
        $program->setStart($start);

        $this->em->transactional(function () use ($program, $block) : void {
            $this->programRepository->save($program);

            if ($block->getMandatory() !== ProgramMandatoryType::AUTO_REGISTERED) {
                return;
            }

            foreach ($this->userRepository->findBlockAllowed($block) as $user) {
                $this->registerProgramImpl($user, $program);
            }
            $this->programRepository->save($program);
        });

        return $program;
    }

    /**
     * Aktualizuje program v harmonogramu.
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function updateProgram(Program $program, ?Room $room, DateTime $start) : void
    {
        $program->setRoom($room);
        $program->setStart($start);
        $this->programRepository->save($program);
    }

    /**
     * Odstraní program z harmonogramu.
     * @throws Throwable
     */
    public function removeProgram(Program $program) : void
    {
        $this->em->transactional(function () use ($program) : void {
//            $attendees = clone $program->getAttendees();

            $this->programRepository->remove($program);

//            $this->updateUsersNotRegisteredMandatoryBlocks($attendees); TODO: optimalizovat
        });
    }

    /**
     * Přihlásí uživatele na program.
     * @param bool $sendEmail Poslat uživateli e-mail o přihlášení?
     * @throws Throwable
     */
    public function registerProgram(User $user, Program $program, bool $sendEmail = false) : void
    {
        $this->em->transactional(function () use ($user, $program, $sendEmail) : void {
            $this->registerProgramImpl($user, $program, $sendEmail);
        });
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws SettingsException
     * @throws Throwable
     * @throws MailingMailCreationException
     */
    private function registerProgramImpl(User $user, Program $program, bool $sendEmail = false) : void
    {
        if ($user->getPrograms()->contains($program)) {
            return;
        }

        $this->programRepository->incrementOccupancy($program);

        $user->addProgram($program);
        $this->userRepository->save($user);

//        if ($program->getBlock()->getMandatory() !== ProgramMandatoryType::VOLUNTARY) {
//            $this->updateUserNotRegisteredMandatoryBlocks($user); TODO: optimalizovat
//        }

        if (! $sendEmail) {
            return;
        }

        $this->mailService->sendMailFromTemplate($user, '', Template::PROGRAM_REGISTERED, [
            TemplateVariable::SEMINAR_NAME => $this->settingsService->getValue(Settings::SEMINAR_NAME),
            TemplateVariable::PROGRAM_NAME => $program->getBlock()->getName(),
        ]);
    }

    /**
     * Odhlásí uživatele z programu.
     * @param bool $sendEmail Poslat uživateli e-mail o odhlášení?
     * @throws Throwable
     */
    public function unregisterProgram(User $user, Program $program, bool $sendEmail = false) : void
    {
        $this->em->transactional(function () use ($user, $program, $sendEmail) : void {
            $this->unregisterProgramImpl($user, $program, $sendEmail);
        });
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws SettingsException
     * @throws Throwable
     * @throws MailingMailCreationException
     */
    private function unregisterProgramImpl(User $user, Program $program, bool $sendEmail = false) : void
    {
        if (! $user->getPrograms()->contains($program)) {
            return;
        }

        $this->programRepository->decrementOccupancy($program);

        $user->removeProgram($program);
        $this->userRepository->save($user);

//        if ($program->getBlock()->getMandatory() !== ProgramMandatoryType::VOLUNTARY) {
//            $this->updateUserNotRegisteredMandatoryBlocks($user); TODO: optimalizovat
//        }

        if (! $sendEmail) {
            return;
        }

        $this->mailService->sendMailFromTemplate($user, '', Template::PROGRAM_UNREGISTERED, [
            TemplateVariable::SEMINAR_NAME => $this->settingsService->getValue(Settings::SEMINAR_NAME),
            TemplateVariable::PROGRAM_NAME => $program->getBlock()->getName(),
        ]);
    }

    /**
     * Je povoleno zapisování programů?
     * @throws SettingsException
     * @throws Throwable
     */
    public function isAllowedRegisterPrograms() : bool
    {
        return $this->settingsService->getValue(Settings::REGISTER_PROGRAMS_TYPE) === ProgramRegistrationType::ALLOWED
            || (
                $this->settingsService->getValue(Settings::REGISTER_PROGRAMS_TYPE) === ProgramRegistrationType::ALLOWED_FROM_TO
                && ($this->settingsService->getDateTimeValue(Settings::REGISTER_PROGRAMS_FROM) === null
                    || $this->settingsService->getDateTimeValue(Settings::REGISTER_PROGRAMS_FROM) <= new DateTime())
                && ($this->settingsService->getDateTimeValue(Settings::REGISTER_PROGRAMS_TO) === null
                    || $this->settingsService->getDateTimeValue(Settings::REGISTER_PROGRAMS_TO) >= new DateTime()
                )
            );
    }

    /**
     * Aktualizuje programy uživatele (odhlásí nepovolené a přihlásí automaticky přihlašované).
     *
     * @throws MailingException
     * @throws MailingMailCreationException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws SettingsException
     * @throws Throwable
     */
    public function updateUserPrograms(User $user) : void
    {
        $oldUsersPrograms    = clone $user->getPrograms();
        $userAllowedPrograms = $this->getUserAllowedPrograms($user);

        foreach ($oldUsersPrograms as $program) {
            if ($userAllowedPrograms->contains($program)) {
                continue;
            }

            $this->unregisterProgramImpl($user, $program);
        }

        foreach ($userAllowedPrograms as $program) {
            if ($program->getBlock()->getMandatory() !== ProgramMandatoryType::AUTO_REGISTERED) {
                continue;
            }

            $this->registerProgramImpl($user, $program);
        }

//        $this->updateUserNotRegisteredMandatoryBlocks($user); TODO: optimalizovat
    }

    /**
     * Aktualizuje programy uživatelů (odhlásí nepovolené a přihlásí automaticky přihlašované).
     *
     * @param Collection|User[] $users
     * @throws MailingException
     * @throws MailingMailCreationException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws SettingsException
     * @throws Throwable
     */
    public function updateUsersPrograms(Collection $users) : void
    {
        foreach ($users as $user) {
            $this->updateUserPrograms($user);
        }
    }

    /**
     * Aktualizuje uživateli seznam nepřihlášených povinných bloků.
     *
     * @throws Exception
     */
    private function updateUserNotRegisteredMandatoryBlocks(User $user, bool $flush = true) : void
    {
        if ($user->isAllowed(Resource::PROGRAM, Permission::CHOOSE_PROGRAMS)) {
            $registerableCategories = $this->categoryRepository->findUserAllowed($user);
            $registeredSubevents    = $user->getSubevents();

            $notRegisteredMandatoryBlocks = $this->blockRepository->findMandatoryForCategoriesAndSubevents($user, $registerableCategories, $registeredSubevents);

            $user->setNotRegisteredMandatoryBlocks($notRegisteredMandatoryBlocks);
        } else {
            $user->setNotRegisteredMandatoryBlocks(new ArrayCollection());
        }

        if (! $flush) {
            return;
        }

        $this->em->flush();
    }

    /**
     * Aktualizuje uživatelům seznam nepřihlášených povinných bloků.
     * @param Collection|User[] $users
     * @throws Exception
     */
    private function updateUsersNotRegisteredMandatoryBlocks(Collection $users) : void
    {
        foreach ($users as $user) {
            $this->updateUserNotRegisteredMandatoryBlocks($user, false);
        }

        $this->em->flush();
    }

    /**
     * Vrací programy, na které se uživatel může přihlásit.
     * @return Collection|Program[]
     */
    public function getUserAllowedPrograms(User $user) : Collection
    {
        if (! $user->isAllowed(Resource::PROGRAM, Permission::CHOOSE_PROGRAMS)) {
            return new ArrayCollection();
        }

        $registerableCategories = $this->categoryRepository->findUserAllowed($user);
        $registeredSubevents    = $user->getSubevents();

        return $this->programRepository->findAllowedForCategoriesAndSubevents($registerableCategories, $registeredSubevents);
    }
}
