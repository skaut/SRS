<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Model\Enums\ProgramMandatoryType;
use App\Model\Enums\ProgramRegistrationType;
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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Nette;
use function implode;

/**
 * Služba pro správu programů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ProgramService
{
    use Nette\SmartObject;

    /** @var SettingsRepository */
    private $settingsRepository;

    /** @var ProgramRepository */
    private $programRepository;

    /** @var BlockRepository */
    private $blockRepository;

    /** @var UserRepository */
    private $userRepository;

    /** @var CategoryRepository */
    private $categoryRepository;


    public function __construct(
        SettingsRepository $settingsRepository,
        ProgramRepository $programRepository,
        BlockRepository $blockRepository,
        UserRepository $userRepository,
        CategoryRepository $categoryRepository
    ) {
        $this->settingsRepository = $settingsRepository;
        $this->programRepository  = $programRepository;
        $this->blockRepository    = $blockRepository;
        $this->userRepository     = $userRepository;
        $this->categoryRepository = $categoryRepository;
    }

    public function createBlock(string $name,
                                Subevent $subevent,
                                ?Category $category,
                                ?User $lector,
                                int $duration,
                                ?int $capacity,
                                string $mandatory,
                                string $perex,
                                string $description,
                                string $tools) : void {

    }

    public function updateBlock(Block $block,
                                string $name,
                                Subevent $subevent,
                                ?Category $category,
                                ?User $lector,
                                int $duration,
                                ?int $capacity,
                                string $mandatory,
                                string $perex,
                                string $description,
                                string $tools) : void {
        $this->blockRepository->getEntityManager()->transactional(function ($em) use ($block, $name, $subevent, $category, $lector, $duration, $capacity, $mandatory, $perex, $description, $tools) : void {
            $oldMandatory = $block->getMandatory();
            $oldSubevent = $block->getSubevent();
            $oldCategory = $block->getCategory();

            $block->setMandatory($mandatory);
            $block->setSubevent($subevent);
            $block->setCategory($category);

            $this->blockRepository->save($block);

            //aktualizace ucastniku pri zmene kategorie nebo podakce
            if ($oldMandatory === $this->block->getMandatory() && (
                    $this->block->getCategory() !== $oldCategory) || ($this->block->getSubevent() !== $oldSubevent)
            ) {
                $this->programService->updateUsersPrograms(new ArrayCollection($this->userRepository->findAll()));
            }
        });
    }

    public function updateBlockMandatory(Block $block, string $mandatory) : void {
        $this->blockRepository->getEntityManager()->transactional(function ($em) use ($block, $mandatory) : void {
            $oldMandatory = $block->getMandatory();

            $block->setMandatory($mandatory);

            $this->blockRepository->save($block);

            //odstraneni ucastniku, pokud se odstrani automaticke prihlasovani
            if ($oldMandatory === ProgramMandatoryType::AUTO_REGISTERED && $mandatory !== ProgramMandatoryType::AUTO_REGISTERED) {
                foreach ($this->block->getPrograms() as $program) {
                    $program->removeAllAttendees();
                }
            }

            //pridani ucastniku, pokud je pridano automaticke prihlaseni
            if ($oldMandatory !== ProgramMandatoryType::AUTO_REGISTERED && $mandatory === ProgramMandatoryType::AUTO_REGISTERED) {
                foreach ($this->block->getPrograms() as $program) {
                    $program->setAttendees($this->userRepository->findProgramAllowed($program));
                }
            }

            $this->blockRepository->save($block);
        });
    }

    public function removeBlock(Block $block) {

    }

    public function createProgram(Block $block, Room $room, \DateTime $start) : void {

    }

    public function updateProgram(Program $program, Room $room, \DateTime $start) : void {

    }

    public function removeProgram(Program $program) : void {

    }

    public function registerProgram(User $user, Program $program) : void {

    }

    public function unregisterProgram(User $user, Program $program) : void {

    }

    /**
     * Je povoleno zapisování programů?
     * @throws SettingsException
     * @throws \Throwable
     */
    public function isAllowedRegisterPrograms() : bool
    {
        return $this->settingsRepository->getValue(Settings::REGISTER_PROGRAMS_TYPE) === ProgramRegistrationType::ALLOWED
            || (
                $this->settingsRepository->getValue(Settings::REGISTER_PROGRAMS_TYPE) === ProgramRegistrationType::ALLOWED_FROM_TO
                && ($this->settingsRepository->getDateTimeValue(Settings::REGISTER_PROGRAMS_FROM) === null
                    || $this->settingsRepository->getDateTimeValue(Settings::REGISTER_PROGRAMS_FROM) <= new \DateTime())
                && ($this->settingsRepository->getDateTimeValue(Settings::REGISTER_PROGRAMS_TO) === null
                    || $this->settingsRepository->getDateTimeValue(Settings::REGISTER_PROGRAMS_TO) >= new \DateTime()
                )
            );
    }

    /**
     * Aktualizuje programy uživatele (odhlásí nepovolené a přihlásí automaticky přihlašované).
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function updateUserPrograms(User $user) : void
    {
        $this->updateUsersPrograms(new ArrayCollection([$user]));
    }

    /**
     * Aktualizuje programy uživatelů (odhlásí nepovolené a přihlásí automaticky přihlašované).
     * @param Collection|User[] $users
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function updateUsersPrograms(Collection $users) : void
    {
        foreach ($users as $user) {
            $oldUsersPrograms    = clone $user->getPrograms();
            $userAllowedPrograms = $this->getUserAllowedPrograms($user);

            $user->getPrograms()->clear();

            foreach ($userAllowedPrograms as $userAllowedProgram) {
                if ($userAllowedProgram->getBlock()->getMandatory() !== 2 && ! $oldUsersPrograms->contains($userAllowedProgram)) {
                    continue;
                }

                $user->addProgram($userAllowedProgram);
            }

            $this->userRepository->save($user);
        }
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

    /**
     * Vrací názvy bloků, které jsou pro uživatele povinné, ale není na ně přihlášený.
     * @return Collection|Block[]
     */
    public function getUnregisteredUserMandatoryBlocks(User $user) : Collection
    {
        $registerableCategories = $this->categoryRepository->findUserAllowed($user);
        $registeredSubevents    = $user->getSubevents();

        return $this->blockRepository->findMandatoryForCategoriesAndSubevents($user, $registerableCategories, $registeredSubevents);
    }

    /**
     * @return Collection|string[]
     */
    public function getUnregisteredUserMandatoryBlocksNames(User $user) : Collection
    {
        return $this->getUnregisteredUserMandatoryBlocks($user)->map(function (Block $block) {
            return $block->getName();
        });
    }

    public function getUnregisteredUserMandatoryBlocksNamesText(User $user) : string
    {
        return implode(', ', $this->getUnregisteredUserMandatoryBlocksNames($user)->toArray());
    }
}
