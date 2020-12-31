<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\Acl\Events\CategoryUpdatedEvent;
use App\Model\Acl\Role;
use App\Model\Enums\ProgramRegistrationType;
use App\Model\Program\Block;
use App\Model\Program\Category;
use App\Model\Program\Events\BlockUpdatedEvent;
use App\Model\Program\Events\ProgramCreatedEvent;
use App\Model\Program\Program;
use App\Model\Program\Repositories\BlockRepository;
use App\Model\Program\Repositories\CategoryRepository;
use App\Model\Program\Repositories\ProgramRepository;
use App\Model\Program\Room;
use App\Model\Settings\Exceptions\SettingsException;
use App\Model\Settings\Settings;
use App\Model\Structure\Subevent;
use App\Model\User\User;
use DateTimeImmutable;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\ORMException;
use eGen\MessageBus\Bus\EventBus;
use Nette;
use Nettrine\ORM\EntityManagerDecorator;
use Throwable;

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

    private CategoryRepository $categoryRepository;

    private EventBus $eventBus;

    public function __construct(
        EntityManagerDecorator $em,
        SettingsService $settingsService,
        ProgramRepository $programRepository,
        BlockRepository $blockRepository,
        CategoryRepository $categoryRepository,
        EventBus $eventBus
    ) {
        $this->em                 = $em;
        $this->settingsService    = $settingsService;
        $this->programRepository  = $programRepository;
        $this->blockRepository    = $blockRepository;
        $this->categoryRepository = $categoryRepository;
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
            $originalCategory  = $block->getCategory();
            $originalSubevent  = $block->getSubevent();
            $originalMandatory = $block->getMandatory();

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

            $this->eventBus->handle(new BlockUpdatedEvent($block, $originalCategory, $originalSubevent, $originalMandatory));
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
            $originalCategory  = $block->getCategory();
            $originalSubevent  = $block->getSubevent();
            $originalMandatory = $block->getMandatory();

            $block->setMandatory($mandatory);

            $this->blockRepository->save($block);

            $this->eventBus->handle(new BlockUpdatedEvent($block, $originalCategory, $originalSubevent, $originalMandatory));
        });
    }

    /**
     * Odstraní programový blok.
     *
     * @throws Throwable
     */
    public function removeBlock(Block $block) : void
    {
        $this->blockRepository->remove($block);
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
            $originalRegisterableRoles = clone $category->getRegisterableRoles();

            $category->setName($name);
            $category->setRegisterableRoles($registerableRoles);

            $this->categoryRepository->save($category);

            $this->eventBus->handle(new CategoryUpdatedEvent($category, $originalRegisterableRoles));
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

        $this->em->transactional(function () use ($program, $room, $start) : void {
            $program->setRoom($room);
            $program->setStart($start);
            $this->programRepository->save($program);

            $this->eventBus->handle(new ProgramCreatedEvent($program));
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
        $this->programRepository->remove($program);
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
