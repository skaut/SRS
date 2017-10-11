<?php

namespace App\Services;

use App\Mailing\TextMail;
use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\Enums\ConditionOperator;
use App\Model\Enums\MaturityType;
use App\Model\Enums\RegisterProgramsType;
use App\Model\Enums\VariableSymbolType;
use App\Model\Mailing\Mail;
use App\Model\Mailing\MailRepository;
use App\Model\Mailing\TemplateRepository;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsRepository;
use App\Model\Structure\DiscountRepository;
use App\Model\Structure\SubeventRepository;
use App\Model\User\ApplicationRepository;
use App\Model\User\User;
use App\Model\User\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Kdyby\Translation\Translator;
use Nette;
use Ublaboo\Mailing\MailFactory;


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
