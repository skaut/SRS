<?php

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
use App\Model\Settings\SettingsRepository;
use App\Model\User\User;
use App\Model\User\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Nette;


/**
 * Služba pro správu programů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ProgramService extends Nette\Object
{
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


    /**
     * ProgramService constructor.
     * @param SettingsRepository $settingsRepository
     * @param ProgramRepository $programRepository
     * @param BlockRepository $blockRepository
     * @param UserRepository $userRepository
     * @param CategoryRepository $categoryRepository
     */
    public function __construct(SettingsRepository $settingsRepository, ProgramRepository $programRepository,
                                BlockRepository $blockRepository, UserRepository $userRepository,
                                CategoryRepository $categoryRepository)
    {
        $this->settingsRepository = $settingsRepository;
        $this->programRepository = $programRepository;
        $this->blockRepository = $blockRepository;
        $this->userRepository = $userRepository;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Je povoleno zapisování programů?
     * @return bool
     * @throws \App\Model\Settings\SettingsException
     */
    public function isAllowedRegisterPrograms()
    {
        return $this->settingsRepository->getValue(Settings::REGISTER_PROGRAMS_TYPE) == RegisterProgramsType::ALLOWED
            || (
                $this->settingsRepository->getValue(Settings::REGISTER_PROGRAMS_TYPE) == RegisterProgramsType::ALLOWED_FROM_TO
                && ($this->settingsRepository->getDateTimeValue(Settings::REGISTER_PROGRAMS_FROM) === NULL
                    || $this->settingsRepository->getDateTimeValue(Settings::REGISTER_PROGRAMS_FROM) <= new \DateTime())
                && ($this->settingsRepository->getDateTimeValue(Settings::REGISTER_PROGRAMS_TO) === NULL
                    || $this->settingsRepository->getDateTimeValue(Settings::REGISTER_PROGRAMS_TO) >= new \DateTime()
                )
            );
    }

    /**
     * Aktualizuje programy uživatele (odhlásí nepovolené a přihlásí automaticky přihlašované).
     * @param User $user
     */
    public function updateUserPrograms(User $user)
    {
        $this->updateUsersPrograms([$user]);
    }

    /**
     * Aktualizuje programy uživatelů (odhlásí nepovolené a přihlásí automaticky přihlašované).
     * @param User[] $users
     */
    public function updateUsersPrograms($users)
    {
        foreach ($users as $user) {
            $oldUsersPrograms = $user->getPrograms();
            $userAllowedPrograms = $this->getUserAllowedPrograms($user);

            $newUsersPrograms = new ArrayCollection();

            foreach ($userAllowedPrograms as $userAllowedProgram) {
                if ($userAllowedProgram->getBlock()->getMandatory() == 2 || $oldUsersPrograms->contains($userAllowedProgram))
                    $newUsersPrograms->add($userAllowedProgram);
            }

            $user->setPrograms($newUsersPrograms);
            $this->userRepository->save($user);
        }
    }

    /**
     * Vrací programy, na které se uživatel může přihlásit.
     * @param User $user
     * @return Collection|Program[]
     */
    public function getUserAllowedPrograms($user): Collection
    {
        if (!$user->isAllowed(Resource::PROGRAM, Permission::CHOOSE_PROGRAMS))
            return new ArrayCollection();

        $registerableCategories = $this->categoryRepository->findUserAllowed($user);
        $registeredSubevents = $user->getSubevents();

        return $this->programRepository->findAllowedForCategoriesAndSubevents($registerableCategories, $registeredSubevents);
    }

    /**
     * Vrací názvy bloků, které jsou pro uživatele povinné, ale není na ně přihlášený.
     * @param User $user
     * @return Collection|Block[]
     */
    public function getUnregisteredUserMandatoryBlocks(User $user): Collection
    {
        $registerableCategories = $this->categoryRepository->findUserAllowed($user);
        $registeredSubevents = $user->getSubevents();

        return $this->blockRepository->findMandatoryForCategoriesAndSubevents($user, $registerableCategories, $registeredSubevents);
    }

    /**
     * @param User $user
     * @return Collection|string[]
     */
    public function getUnregisteredUserMandatoryBlocksNames(User $user): Collection
    {
        return $this->getUnregisteredUserMandatoryBlocks($user)->map(function (Block $block) {return $block->getName();});
    }

    /**
     * @param User $user
     * @return string
     */
    public function getUnregisteredUserMandatoryBlocksNamesText(User $user): string
    {
        return implode(', ', $this->getUnregisteredUserMandatoryBlocksNames($user)->toArray());
    }
}
