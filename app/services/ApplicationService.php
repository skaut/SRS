<?php

namespace App\Services;

use App\Mailing\TextMail;
use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\Enums\ConditionOperator;
use App\Model\Enums\MaturityType;
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
 * Služba pro správu přihlašování na akci.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ApplicationService extends Nette\Object
{
    /** @var SettingsRepository */
    private $settingsRepository;

    /** @var ApplicationRepository */
    private $applicationRepository;

    /** @var UserRepository */
    private $userRepository;

    /** @var DiscountRepository */
    private $discountRepository;

    /** @var RoleRepository */
    private $roleRepository;

    /** @var SubeventRepository */
    private $subeventRepository;


    /**
     * ApplicationService constructor.
     * @param SettingsRepository $settingsRepository
     * @param ApplicationRepository $applicationRepository
     * @param UserRepository $userRepository
     * @param DiscountRepository $discountRepository
     * @param RoleRepository $roleRepository
     */
    public function __construct(SettingsRepository $settingsRepository, ApplicationRepository $applicationRepository,
                                UserRepository $userRepository, DiscountRepository $discountRepository,
                                RoleRepository $roleRepository, SubeventRepository $subeventRepository)
    {
        $this->settingsRepository = $settingsRepository;
        $this->applicationRepository = $applicationRepository;
        $this->userRepository = $userRepository;
        $this->discountRepository = $discountRepository;
        $this->roleRepository = $roleRepository;
        $this->subeventRepository = $subeventRepository;
    }

    /**
     * Vygeneruje variabilní symbol.
     * @param User $user
     * @return string
     */
    public function generateVariableSymbol(User $user) {
        $variableSymbolCode = $this->settingsRepository->getValue(Settings::VARIABLE_SYMBOL_CODE);
        $variableSymbol = "";

        switch ($this->settingsRepository->getValue(Settings::VARIABLE_SYMBOL_TYPE)) {
            case VariableSymbolType::BIRTH_DATE:
                $variableSymbolDate = $user->getBirthdate()->format('ymd');
                $variableSymbol = $variableSymbolCode . $variableSymbolDate;

                while ($this->userRepository->variableSymbolExists($variableSymbol)) {
                    $variableSymbolDate = str_pad($variableSymbolDate + 1, 6, 0, STR_PAD_LEFT);
                    $variableSymbol = $variableSymbolCode . $variableSymbolDate;
                }

                break;

            case VariableSymbolType::ORDER:
                $applicationOrder = $this->applicationRepository->findLastApplicationOrder()+1;
                $variableSymbol = $variableSymbolCode . str_pad($applicationOrder, 6, '0', STR_PAD_LEFT);
                break;
        }

        return $variableSymbol;
    }

    /**
     * Vypočítá datum splatnosti podle zvolené metody.
     * @return \DateTime|null
     */
    public function countMaturityDate() {
        switch ($this->settingsRepository->getValue(Settings::MATURITY_TYPE)) {
            case MaturityType::DATE:
                return $this->settingsRepository->getDateValue(Settings::MATURITY_DATE);

            case MaturityType::DAYS:
                return (new \DateTime())->modify('+' . $this->settingsRepository->getValue(Settings::MATURITY_DAYS) . ' days');

            case MaturityType::WORK_DAYS:
                $currentDate = (new \DateTime())->format('Y-m-d');
                $workDays = $this->settingsRepository->getValue(Settings::MATURITY_WORK_DAYS);
                return new \DateTime(date('Y-m-d', strftime($currentDate . ' +' . $workDays . ' Weekday')));
        }
        return NULL;
    }

    /**
     * Vypočítá poplatek.
     * @param $roles
     * @param $subevents
     * @param bool $first
     * @return int
     */
    public function countFee($roles, $subevents, $first = TRUE) {
        $fee = 0;

        if ($roles !== NULL) {
            $subeventsFeeRole = FALSE;

            foreach ($roles as $role) {
                //cena podle podakci
                if ($role->getFee() === NULL) {
                    if ($subevents !== NULL && !$subeventsFeeRole) {
                        foreach ($subevents as $subevent) {
                            $fee += $subevent->getFee();
                        }
                        $subeventsFeeRole = TRUE;
                    }
                }
                //neplatici role
                elseif ($role->getFee() == 0) {
                    return 0;
                }
                //role s pevnym poplatkem
                elseif ($first) {
                    $fee += $role->getFee();
                }
            }
        }

        //sleva
        foreach ($this->discountRepository->findAll() as $discount) {
            switch ($discount->getConditionOperator()) {
                case ConditionOperator::OPERATOR_AND:
                    $res = TRUE;
                    foreach ($discount->getConditionSubevents() as $conditionSubevent) {
                        if (!$subevents->contains($conditionSubevent)) {
                            $res = FALSE;
                            break;
                        }
                    }
                    if ($res)
                        $fee -= $discount->getDiscount();
                    break;

                case ConditionOperator::OPERATOR_OR:
                    foreach ($discount->getConditionSubevents() as $conditionSubevent) {
                        if ($subevents->contains($conditionSubevent)) {
                            $fee -= $discount->getDiscount();
                            break;
                        }
                    }
                    break;
            }
        }

        return $fee;
    }

    /**
     * Může uživatel měnit registraci?
     * @param User $user
     * @return bool
     */
    public function isAllowedEditRegistration(User $user)
    {
        $nonregisteredRole = $this->roleRepository->findBySystemName(Role::NONREGISTERED);
        return !$user->isInRole($nonregisteredRole)
            && !$user->hasPaidFirstApplication()
            && $this->settingsRepository->getDateValue(Settings::EDIT_REGISTRATION_TO) >= (new \DateTime())->setTime(0, 0);
    }

    /**
     * Může uživatel dodatečně přidávat podakce?
     * @param User $user
     * @return bool
     */
    public function isAllowedAddSubevents(User $user)
    {
        return $user->hasPaidFirstApplication()
            && $this->subeventRepository->explicitSubeventsExists()
            && $this->settingsRepository->getValue(Settings::IS_ALLOWED_ADD_SUBEVENTS_AFTER_PAYMENT)
            && $this->settingsRepository->getDateValue(Settings::EDIT_REGISTRATION_TO) >= (new \DateTime())->setTime(0, 0);
    }

    /**
     * Může uživatel upravovat první přihlášku?
     * @param User $user
     * @return bool
     */
    public function isAllowedEditFirstApplication(User $user)
    {
        return !$user->hasPaidFirstApplication()
            && $this->settingsRepository->getDateValue(Settings::EDIT_REGISTRATION_TO) >= (new \DateTime())->setTime(0, 0);
    }
}
