<?php

namespace App\Services;

use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Model\Enums\RegisterProgramsType;
use App\Model\Program\BlockRepository;
use App\Model\Program\ProgramRepository;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsRepository;
use App\Model\User\User;
use App\Model\User\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
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


    /**
     * ProgramService constructor.
     * @param SettingsRepository $settingsRepository
     * @param ProgramRepository $programRepository
     * @param BlockRepository $blockRepository
     * @param UserRepository $userRepository
     */
    public function __construct(SettingsRepository $settingsRepository, ProgramRepository $programRepository,
                                BlockRepository $blockRepository, UserRepository $userRepository)
    {
        $this->settingsRepository = $settingsRepository;
        $this->programRepository = $programRepository;
        $this->blockRepository = $blockRepository;
        $this->userRepository = $userRepository;
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

            $oldUsersPrograms->clear();
            $user->setPrograms($newUsersPrograms);
            $this->userRepository->save($user);
        }
    }

    /**
     * Vrací programy, na které se uživatel může přihlásit.
     * @param User $user
     * @return array
     */
    public function getUserAllowedPrograms($user)
    {
        if (!$user->isAllowed(Resource::PROGRAM, Permission::CHOOSE_PROGRAMS))
            return [];

        $registerableCategoriesIds = $this->userRepository->findRegisterableCategoriesIdsByUser($user);

        $registeredSubeventsIds = [];

        foreach ($user->getNotCanceledApplications() as $application) {
            foreach ($application->getSubevents() as $subevent)
                $registeredSubeventsIds[] = $subevent->getId();
        }

        return $this->programRepository->findAllowedForCategoriesAndSubevents($registerableCategoriesIds, $registeredSubeventsIds);
    }

    /**
     * Vrací názvy bloků, které jsou pro uživatele povinné, ale není na ně přihlášený.
     * @param User $user
     * @return array
     */
    public function getUnregisteredUserMandatoryBlocksNames($user)
    {
        $registerableCategoriesIds = $this->userRepository->findRegisterableCategoriesIdsByUser($user);
        $usersSubevents = $user->getSubevents();

        $unregisteredBlocks = $this->blockRepository->findMandatoryForCategoriesAndSubevents($user, $registerableCategoriesIds, $usersSubevents);

        $names = [];
        foreach ($unregisteredBlocks as $block)
            $names[] = $block->getName();

        return $names;
    }

}
