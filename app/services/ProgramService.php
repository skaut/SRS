<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Model\Enums\RegisterProgramsType;
use App\Model\Program\Block;
use App\Model\Program\BlockRepository;
use App\Model\Program\CategoryRepository;
use App\Model\Program\Program;
use App\Model\Program\ProgramRepository;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Model\Settings\SettingsRepository;
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

    /**
     * Je povoleno zapisování programů?
     * @throws SettingsException
     * @throws \Throwable
     */
    public function isAllowedRegisterPrograms() : bool
    {
        return $this->settingsRepository->getValue(Settings::REGISTER_PROGRAMS_TYPE) === RegisterProgramsType::ALLOWED
            || (
                $this->settingsRepository->getValue(Settings::REGISTER_PROGRAMS_TYPE) === RegisterProgramsType::ALLOWED_FROM_TO
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
        $this->updateUsersPrograms([$user]);
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
