<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\Acl\Events\CategoryChangedEvent;
use App\Model\Acl\Permission;
use App\Model\Acl\Role;
use App\Model\Acl\SrsResource;
use App\Model\Enums\ProgramMandatoryType;
use App\Model\Enums\ProgramRegistrationType;
use App\Model\Mailing\Template;
use App\Model\Mailing\TemplateVariable;
use App\Model\Program\Block;
use App\Model\Program\Category;
use App\Model\Program\Program;
use App\Model\Program\Repositories\BlockRepository;
use App\Model\Program\Repositories\CategoryRepository;
use App\Model\Program\Repositories\ProgramRepository;
use App\Model\Program\Room;
use App\Model\Settings\Exceptions\SettingsException;
use App\Model\Settings\Settings;
use App\Model\Structure\Subevent;
use App\Model\User\Repositories\UserRepository;
use App\Model\User\User;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\ORMException;
use eGen\MessageBus\Bus\EventBus;
use Eluceo\iCal\Component\Event;
use Nette;
use Nettrine\ORM\EntityManagerDecorator;
use Throwable;
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

    private EntityManagerDecorator $em;

    private SettingsService $settingsService;

    private ProgramRepository $programRepository;

    private BlockRepository $blockRepository;

    private UserRepository $userRepository;

    private CategoryRepository $categoryRepository;

    private MailService $mailService;

    private EventBus $eventBus;

    public function __construct(
        EntityManagerDecorator $em,
        SettingsService $settingsService,
        ProgramRepository $programRepository,
        BlockRepository $blockRepository,
        UserRepository $userRepository,
        CategoryRepository $categoryRepository,
        MailService $mailService,
        EventBus $eventBus
    ) {
        $this->em                 = $em;
        $this->settingsService    = $settingsService;
        $this->programRepository  = $programRepository;
        $this->blockRepository    = $blockRepository;
        $this->userRepository     = $userRepository;
        $this->categoryRepository = $categoryRepository;
        $this->mailService        = $mailService;
        $this->eventBus           = $eventBus;
    }

    /**
     * Vytvoří programový blok.
     *
     * @param Collection|User[] $lectors
     *
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
        });
    }

    /**
     * Aktualizuje programový blok.
     *
     * @param Collection|User[] $lectors
     *
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
            if (($category === null && $oldCategory !== null)
                || ($category !== null && $oldCategory === null)
                || ($category !== null && $oldCategory !== null && $category->getId() !== $oldCategory->getId())
                || ($subevent->getId() !== $oldSubevent->getId())) {
                $allowedUsers = $this->userRepository->findBlockAllowed($block);

                foreach ($block->getPrograms() as $program) {
                    foreach ($program->getAttendees() as $user) {
                        if (! $allowedUsers->contains($user)) {
                            $this->unregisterProgramImpl($user, $program);
                        }
                    }

                    if ($mandatory === ProgramMandatoryType::AUTO_REGISTERED) {
                        foreach ($allowedUsers as $user) {
                            $this->registerProgramImpl($user, $program);
                        }
                    }
                }
            }

            //aktualizace ucastniku pri zmene povinnosti
            $this->updateBlockMandatoryImpl($block, $mandatory);
        });
    }

    /**
     * Aktualizuje povinnost bloku.
     *
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
     * @throws SettingsException
     * @throws Throwable
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

        $this->blockRepository->save($block);
    }

    /**
     * Odstraní programový blok.
     *
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
        });
    }

    /**
     * Vytvoří kategorii programů.
     *
     * @param Collection|Role[] $registerableRoles
     *
     * @throws ORMException
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
     *
     * @param Collection|Role[] $registerableRoles
     *
     * @throws Throwable
     */
    public function updateCategory(Category $category, string $name, Collection $registerableRoles) : void
    {
        $this->em->transactional(function () use ($category, $name, $registerableRoles) : void {
            $category->setName($name);
            $category->setRegisterableRoles($registerableRoles);

            $this->categoryRepository->save($category);

            $this->eventBus->handle(new CategoryChangedEvent());
        });
    }

    /**
     * Vytvoří program v harmonogramu.
     *
     * @throws Throwable
     */
    public function createProgram(Block $block, ?Room $room, DateTimeImmutable $start) : Program
    {
        $program = new Program($block);

        $program->setRoom($room);
        $program->setStart($start);

        $this->em->transactional(function () use ($program, $block) : void {
            $this->programRepository->save($program);

            if ($block->getMandatory() === ProgramMandatoryType::AUTO_REGISTERED) {
                foreach ($this->userRepository->findBlockAllowed($block) as $user) {
                    $this->registerProgramImpl($user, $program);
                }

                $this->programRepository->save($program);
            }
        });

        return $program;
    }

    /**
     * Aktualizuje program v harmonogramu.
     *
     * @throws ORMException
     */
    public function updateProgram(Program $program, ?Room $room, DateTimeImmutable $start) : void
    {
        $program->setRoom($room);
        $program->setStart($start);
        $this->programRepository->save($program);
    }

    /**
     * Odstraní program z harmonogramu.
     *
     * @throws Throwable
     */
    public function removeProgram(Program $program) : void
    {
        $this->em->transactional(function () use ($program) : void {
            $this->programRepository->remove($program);
        });
    }

    /**
     * Je povoleno zapisování programů?
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function isAllowedRegisterPrograms() : bool
    {
        return $this->settingsService->getValue(Settings::REGISTER_PROGRAMS_TYPE) === ProgramRegistrationType::ALLOWED
            || (
                $this->settingsService->getValue(Settings::REGISTER_PROGRAMS_TYPE) === ProgramRegistrationType::ALLOWED_FROM_TO
                && ($this->settingsService->getDateTimeValue(Settings::REGISTER_PROGRAMS_FROM) === null
                    || $this->settingsService->getDateTimeValue(Settings::REGISTER_PROGRAMS_FROM) <= new DateTimeImmutable())
                && ($this->settingsService->getDateTimeValue(Settings::REGISTER_PROGRAMS_TO) === null
                    || $this->settingsService->getDateTimeValue(Settings::REGISTER_PROGRAMS_TO) >= new DateTimeImmutable()
                )
            );
    }
}
