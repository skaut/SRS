<?php

namespace App\Services;

use App\Model\Enums\RegisterProgramsType;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsRepository;
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


    /**
     * ProgramService constructor.
     * @param SettingsRepository $settingsRepository
     */
    public function __construct(SettingsRepository $settingsRepository)
    {
        $this->settingsRepository = $settingsRepository;
    }

    /**
     * Je povoleno zapisování programů?
     * @return bool
     */
    public function isAllowedRegisterPrograms()
    {
        return $this->settingsRepository->getValue(Settings::REGISTER_PROGRAMS_TYPE) ==  RegisterProgramsType::ALLOWED
            || (
                $this->settingsRepository->getValue(Settings::REGISTER_PROGRAMS_TYPE) ==  RegisterProgramsType::ALLOWED_FROM_TO
                && ($this->settingsRepository->getDateTimeValue(Settings::REGISTER_PROGRAMS_FROM) === NULL
                    || $this->settingsRepository->getDateTimeValue(Settings::REGISTER_PROGRAMS_FROM) <= new \DateTime())
                && ($this->settingsRepository->getDateTimeValue(Settings::REGISTER_PROGRAMS_TO) === NULL
                    || $this->settingsRepository->getDateTimeValue(Settings::REGISTER_PROGRAMS_TO) >= new \DateTime()
                )
            );
    }
}
