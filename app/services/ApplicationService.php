<?php
declare(strict_types=1);

namespace App\Services;

use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\Enums\ApplicationState;
use App\Model\Enums\MaturityType;
use App\Model\Mailing\Template;
use App\Model\Mailing\TemplateVariable;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsRepository;
use App\Model\Structure\DiscountRepository;
use App\Model\Structure\Subevent;
use App\Model\Structure\SubeventRepository;
use App\Model\User\Application;
use App\Model\User\ApplicationRepository;
use App\Model\User\RolesApplication;
use App\Model\User\SubeventsApplication;
use App\Model\User\User;
use App\Model\User\UserRepository;
use App\Model\User\VariableSymbol;
use App\Model\User\VariableSymbolRepository;
use App\Utils\Helpers;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Kdyby\Translation\Translator;
use Nette;
use \Yasumi\Yasumi;


/**
 * Služba pro správu přihlašování na akci.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ApplicationService
{
    use Nette\SmartObject;

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

    /** @var DiscountService */
    private $discountService;

    /** @var VariableSymbolRepository */
    private $variableSymbolRepository;

    /** @var ProgramService */
    private $programService;

    /** @var MailService */
    private $mailService;

    /** @var Translator */
    private $translator;


    /**
     * ApplicationService constructor.
     * @param SettingsRepository $settingsRepository
     * @param ApplicationRepository $applicationRepository
     * @param UserRepository $userRepository
     * @param DiscountRepository $discountRepository
     * @param RoleRepository $roleRepository
     * @param SubeventRepository $subeventRepository
     * @param DiscountService $discountService
     * @param VariableSymbolRepository $variableSymbolRepository
     * @param ProgramService $programService
     * @param MailService $mailService
     * @param Translator $translator
     */
    public function __construct(SettingsRepository $settingsRepository, ApplicationRepository $applicationRepository,
                                UserRepository $userRepository, DiscountRepository $discountRepository,
                                RoleRepository $roleRepository, SubeventRepository $subeventRepository,
                                DiscountService $discountService, VariableSymbolRepository $variableSymbolRepository,
                                ProgramService $programService, MailService $mailService, Translator $translator)
    {
        $this->settingsRepository = $settingsRepository;
        $this->applicationRepository = $applicationRepository;
        $this->userRepository = $userRepository;
        $this->discountRepository = $discountRepository;
        $this->roleRepository = $roleRepository;
        $this->subeventRepository = $subeventRepository;
        $this->discountService = $discountService;
        $this->variableSymbolRepository = $variableSymbolRepository;
        $this->programService = $programService;
        $this->mailService = $mailService;
        $this->translator = $translator;
    }

    /**
     * @param User $user
     * @param Collection $roles
     * @param Collection $subevents
     * @param User $createdBy
     * @param bool $approve
     * @throws \Throwable
     */
    public function register(User $user, Collection $roles, Collection $subevents, User $createdBy,
                             bool $approve = FALSE): void
    {
        $rolesApplication = new RolesApplication();
        $subeventsApplication = new SubeventsApplication();

        $this->applicationRepository->getEntityManager()->transactional(function ($em) use ($user, $roles, $subevents, $createdBy, $approve, &$rolesApplication, &$subeventsApplication) {
            $rolesApplication = $this->createRolesApplication($user, $roles, $createdBy, $approve);
            $subeventsApplication = $this->createSubeventsApplication($user, $subevents, $createdBy);

            $this->programService->updateUserPrograms($user);
        });

        $applicatonMaturity = "-";
        $applicationFee = "0";
        $applicationVariableSymbol = "-";

        if ($rolesApplication->getFee() > 0 && $subeventsApplication->getFee() > 0) {
            if ($rolesApplication->getMaturityDate())
                $applicatonMaturity = $rolesApplication->getMaturityDateText();
            $applicationFee = $rolesApplication->getFee() . ", " . $subeventsApplication->getFee();
            $applicationVariableSymbol = $rolesApplication->getVariableSymbolText() . ", "
                . $subeventsApplication->getVariableSymbolText();
        } elseif ($rolesApplication->getFee() > 0) {
            if ($rolesApplication->getMaturityDate())
                $applicatonMaturity = $rolesApplication->getMaturityDateText();
            $applicationFee = $rolesApplication->getFee();
            $applicationVariableSymbol = $rolesApplication->getVariableSymbolText();
        } elseif ($subeventsApplication->getFee() > 0) {
            if ($subeventsApplication->getMaturityDate())
                $applicatonMaturity = $subeventsApplication->getMaturityDateText();
            $applicationFee = $subeventsApplication->getFee();
            $applicationVariableSymbol = $subeventsApplication->getVariableSymbolText();
        }

        $editRegistrationToText = $this->settingsRepository->getDateValueText(Settings::EDIT_REGISTRATION_TO);

        $this->mailService->sendMailFromTemplate($user, '', Template::REGISTRATION, [
            TemplateVariable::SEMINAR_NAME => $this->settingsRepository->getValue(Settings::SEMINAR_NAME),
            TemplateVariable::EDIT_REGISTRATION_TO => $editRegistrationToText !== NULL ? $editRegistrationToText : '-',
            TemplateVariable::APPLICATION_MATURITY => $applicatonMaturity,
            TemplateVariable::APPLICATION_FEE => $applicationFee,
            TemplateVariable::APPLICATION_VARIABLE_SYMBOL => $applicationVariableSymbol,
            TemplateVariable::BANK_ACCOUNT => $this->settingsRepository->getValue(Settings::ACCOUNT_NUMBER)
        ]);
    }

    /**
     * @param User $user
     * @param Collection $roles
     * @param User $createdBy
     * @param bool $approve
     * @return void
     * @throws \App\Model\Settings\SettingsException
     * @throws \Throwable
     * @throws \Ublaboo\Mailing\Exception\MailingException
     * @throws \Ublaboo\Mailing\Exception\MailingMailCreationException
     */
    public function updateRoles(User $user, Collection $roles, ?User $createdBy, bool $approve = FALSE): void
    {
        $oldRoles = clone $user->getRoles();

        //pokud se role nezmenily, nic se neprovede
        if ($roles->count() == $oldRoles->count()) {
            $rolesArray = $roles->map(function (Role $role) {
                return $role->getId();
            })->toArray();
            $oldRolesArray = $oldRoles->map(function (Role $role) {
                return $role->getId();
            })->toArray();

            if (array_diff($rolesArray, $oldRolesArray) === array_diff($oldRolesArray, $rolesArray))
                return;
        }

        $this->applicationRepository->getEntityManager()->transactional(function ($em) use ($user, $roles, $createdBy, $approve, $oldRoles) {
            if ($oldRoles->contains($this->roleRepository->findBySystemName(Role::NONREGISTERED))) {
                $this->createRolesApplication($user, $roles, $createdBy, $approve);
                $this->createSubeventsApplication($user, new ArrayCollection([$this->subeventRepository->findImplicit()]), $createdBy);
            } else {
                $user->setRoles($roles);
                $this->userRepository->save($user);

                if ($roles->forAll(function (int $key, Role $role) {
                    return $role->isApprovedAfterRegistration();
                })) {
                    $user->setApproved(TRUE);
                } elseif (!$approve && $roles->exists(function (int $key, Role $role) use ($oldRoles) {
                    return !$role->isApprovedAfterRegistration() && !$oldRoles->contains($role);
                })) {
                    $user->setApproved(FALSE);
                }

                foreach ($user->getNotCanceledApplications() as $application) {
                    if ($application->getType() == Application::ROLES) {
                        $newApplication = clone $application;
                        $newApplication->setRoles($roles);
                        $newApplication->setFee($this->countRolesFee($roles));
                        $newApplication->setState($this->getApplicationState($newApplication));
                        $newApplication->setCreatedBy($createdBy);
                        $newApplication->setValidFrom(new \DateTime());
                        $this->applicationRepository->save($newApplication);

                        $application->setValidTo(new \DateTime());
                        $this->applicationRepository->save($application);
                    } else {
                        $fee = $this->countSubeventsFee($roles, $application->getSubevents());

                        if ($application->getFee() != $fee) {
                            $newApplication = clone $application;
                            $newApplication->setFee($fee);
                            $newApplication->setState($this->getApplicationState($newApplication));
                            $newApplication->setCreatedBy($createdBy);
                            $newApplication->setValidFrom(new \DateTime());
                            $this->applicationRepository->save($newApplication);

                            $application->setValidTo(new \DateTime());
                            $this->applicationRepository->save($application);
                        }
                    }
                }

                $this->userRepository->save($user);
            }

            $this->programService->updateUserPrograms($user);
        });

        $this->mailService->sendMailFromTemplate($user, '', Template::ROLES_CHANGED, [
            TemplateVariable::SEMINAR_NAME => $this->settingsRepository->getValue(Settings::SEMINAR_NAME),
            TemplateVariable::USERS_ROLES => implode(', ', $roles->map(function (Role $role) {
                return $role->getName();
            })->toArray())
        ]);
    }

    /**
     * @param User $user
     * @param string $state
     * @param User $createdBy
     * @throws \App\Model\Settings\SettingsException
     * @throws \Throwable
     * @throws \Ublaboo\Mailing\Exception\MailingException
     * @throws \Ublaboo\Mailing\Exception\MailingMailCreationException
     */
    public function cancelRegistration(User $user, string $state, ?User $createdBy): void
    {
        $this->applicationRepository->getEntityManager()->transactional(function ($em) use ($user, $state, $createdBy) {
            $user->setApproved(TRUE);
            $user->getRoles()->clear();
            $user->addRole($this->roleRepository->findBySystemName(Role::NONREGISTERED));
            $this->userRepository->save($user);

            foreach ($user->getNotCanceledApplications() as $application) {
                $newApplication = clone $application;
                $newApplication->setState($state);
                $newApplication->setCreatedBy($createdBy);
                $newApplication->setValidFrom(new \DateTime());
                $this->applicationRepository->save($newApplication);

                $application->setValidTo(new \DateTime());
                $this->applicationRepository->save($application);
            }

            $this->userRepository->save($user);

            $this->programService->updateUserPrograms($user);
        });

        $this->mailService->sendMailFromTemplate($user, '', Template::REGISTRATION_CANCELED, [
            TemplateVariable::SEMINAR_NAME => $this->settingsRepository->getValue(Settings::SEMINAR_NAME)
        ]);
    }

    /**
     * @param User $user
     * @param Collection $subevents
     * @param User $createdBy
     * @throws \Throwable
     */
    public function addSubeventsApplication(User $user, Collection $subevents, User $createdBy): void
    {
        $this->applicationRepository->getEntityManager()->transactional(function ($em) use ($user, $subevents, $createdBy) {
            $this->createSubeventsApplication($user, $subevents, $createdBy);

            $this->programService->updateUserPrograms($user);
        });

        $this->mailService->sendMailFromTemplate($user, '', Template::SUBEVENTS_CHANGED, [
            TemplateVariable::SEMINAR_NAME => $this->settingsRepository->getValue(Settings::SEMINAR_NAME),
            TemplateVariable::USERS_SUBEVENTS => $user->getSubeventsText()
        ]);
    }

    /**
     * @param SubeventsApplication $application
     * @param Collection $subevents
     * @param User $createdBy
     * @throws \App\Model\Settings\SettingsException
     * @throws \Throwable
     * @throws \Ublaboo\Mailing\Exception\MailingException
     * @throws \Ublaboo\Mailing\Exception\MailingMailCreationException
     */
    public function updateSubeventsApplication(SubeventsApplication $application, Collection $subevents, User $createdBy): void
    {
        $oldSubevents = clone $application->getSubevents();

        //pokud se podakce nezmenily, nic se neprovede
        if ($subevents->count() == $oldSubevents->count()) {
            $subeventsArray = $subevents->map(function (Subevent $subevent) {
                return $subevent->getId();
            })->toArray();
            $oldSubeventsArray = $oldSubevents->map(function (Subevent $subevent) {
                return $subevent->getId();
            })->toArray();

            if (array_diff($subeventsArray, $oldSubeventsArray) === array_diff($oldSubeventsArray, $subeventsArray))
                return;
        }

        $this->applicationRepository->getEntityManager()->transactional(function ($em) use ($application, $subevents, $createdBy) {
            $user = $application->getUser();

            $newApplication = clone $application;
            $newApplication->setSubevents($subevents);
            $newApplication->setFee($this->countSubeventsFee($user->getRoles(), $subevents));
            $newApplication->setState($this->getApplicationState($newApplication));
            $newApplication->setCreatedBy($createdBy);
            $newApplication->setValidFrom(new \DateTime());
            $this->applicationRepository->save($newApplication);

            $application->setValidTo(new \DateTime());
            $this->applicationRepository->save($application);

            $this->programService->updateUserPrograms($user);
        });

        $this->mailService->sendMailFromTemplate($application->getUser(), '', Template::SUBEVENTS_CHANGED, [
            TemplateVariable::SEMINAR_NAME => $this->settingsRepository->getValue(Settings::SEMINAR_NAME),
            TemplateVariable::USERS_SUBEVENTS => $application->getUser()->getSubeventsText()
        ]);
    }

    /**
     * @param SubeventsApplication $application
     * @param string $state
     * @param User $createdBy
     * @throws \App\Model\Settings\SettingsException
     * @throws \Throwable
     * @throws \Ublaboo\Mailing\Exception\MailingException
     * @throws \Ublaboo\Mailing\Exception\MailingMailCreationException
     */
    public function cancelSubeventsApplication(SubeventsApplication $application, string $state, ?User $createdBy): void
    {
        $this->applicationRepository->getEntityManager()->transactional(function ($em) use ($application, $state, $createdBy) {
            $user = $application->getUser();

            $newApplication = clone $application;
            $newApplication->setState($state);
            $newApplication->setCreatedBy($createdBy);
            $newApplication->setValidFrom(new \DateTime());
            $this->applicationRepository->save($newApplication);

            $application->setValidTo(new \DateTime());
            $this->applicationRepository->save($application);

            $this->programService->updateUserPrograms($user);
        });

        $this->mailService->sendMailFromTemplate($application->getUser(), '', Template::SUBEVENTS_CHANGED, [
            TemplateVariable::SEMINAR_NAME => $this->settingsRepository->getValue(Settings::SEMINAR_NAME),
            TemplateVariable::USERS_SUBEVENTS => $application->getUser()->getSubeventsText()
        ]);
    }

    /**
     * @param Application $application
     * @param string $variableSymbol
     * @param string $paymentMethod
     * @param \DateTime $paymentDate
     * @param \DateTime $incomeProofPrintedDate
     * @param \DateTime $maturityDate
     * @param User $createdBy
     * @throws \Throwable
     */
    public function updatePayment(Application $application, string $variableSymbol, ?string $paymentMethod,
                                  ?\DateTime $paymentDate, ?\DateTime $incomeProofPrintedDate, ?\DateTime $maturityDate,
                                  User $createdBy): void
    {
        $oldVariableSymbol = $application->getVariableSymbolText();
        $oldPaymentMethod = $application->getPaymentMethod();
        $oldPaymentDate = $application->getPaymentDate();
        $oldIncomeProofPrintedDate = $application->getIncomeProofPrintedDate();
        $oldMaturityDate = $application->getMaturityDate();

        //pokud neni zmena, nic se neprovede
        if ($variableSymbol == $oldVariableSymbol && $paymentMethod == $oldPaymentMethod
            && $paymentDate == $oldPaymentDate && $incomeProofPrintedDate == $oldIncomeProofPrintedDate
            && $maturityDate == $oldMaturityDate)
            return;

        $this->applicationRepository->getEntityManager()->transactional(function ($em) use (
            $application,
            $variableSymbol, $paymentMethod, $paymentDate, $incomeProofPrintedDate, $maturityDate, $createdBy
        ) {
            $user = $application->getUser();

            $newApplication = clone $application;

            if ($application->getVariableSymbolText() != $variableSymbol) {
                $newVariableSymbol = new VariableSymbol();
                $newVariableSymbol->setVariableSymbol($variableSymbol);
                $this->variableSymbolRepository->save($newVariableSymbol);

                $newApplication->setVariableSymbol($newVariableSymbol);
            }

            $newApplication->setPaymentMethod($paymentMethod);
            $newApplication->setPaymentDate($paymentDate);
            $newApplication->setIncomeProofPrintedDate($incomeProofPrintedDate);
            $newApplication->setMaturityDate($maturityDate);

            $newApplication->setState($this->getApplicationState($newApplication));
            $newApplication->setCreatedBy($createdBy);
            $newApplication->setValidFrom(new \DateTime());
            $this->applicationRepository->save($newApplication);

            $application->setValidTo(new \DateTime());
            $this->applicationRepository->save($application);

            $this->programService->updateUserPrograms($user);
        });

        if ($paymentDate !== NULL && $oldPaymentDate === NULL) {
            $this->mailService->sendMailFromTemplate($application->getUser(), '', Template::PAYMENT_CONFIRMED, [
                TemplateVariable::SEMINAR_NAME => $this->settingsRepository->getValue(Settings::SEMINAR_NAME),
                TemplateVariable::APPLICATION_SUBEVENTS => $application->getSubeventsText()
            ]);
        }
    }

    /**
     * @param User $user
     * @param Collection $roles
     * @param User $createdBy
     * @param bool $approve
     * @return RolesApplication
     * @throws \App\Model\Settings\SettingsException
     */
    private function createRolesApplication(User $user, Collection $roles, User $createdBy, bool $approve = FALSE): RolesApplication
    {
        if (!$user->isInRole($this->roleRepository->findBySystemName(Role::NONREGISTERED)))
            throw new \InvalidArgumentException("User is already registered.");

        $user->setApproved(TRUE);
        if (!$approve && $roles->exists(function (int $key, Role $role) {
            return !$role->isApprovedAfterRegistration();
        }))
            $user->setApproved(FALSE);

        $user->setRoles($roles);
        $this->userRepository->save($user);

        $application = new RolesApplication();
        $application->setUser($user);
        $application->setRoles($roles);
        $application->setApplicationDate(new \DateTime());
        $application->setFee($this->countRolesFee($roles));
        $application->setMaturityDate($this->countMaturityDate());
        $application->setState($this->getApplicationState($application));
        $application->setVariableSymbol($this->generateVariableSymbol());
        $application->setCreatedBy($createdBy);
        $application->setValidFrom(new \DateTime());
        $this->applicationRepository->save($application);

        $application->setApplicationId($application->getId());
        $this->applicationRepository->save($application);

        return $application;
    }

    /**
     * @param User $user
     * @param Collection $subevents
     * @param User $createdBy
     * @return SubeventsApplication
     * @throws \App\Model\Settings\SettingsException
     */
    private function createSubeventsApplication(User $user, Collection $subevents,
                                                User $createdBy): SubeventsApplication
    {
        $application = new SubeventsApplication();
        $application->setUser($user);
        $application->setSubevents($subevents);
        $application->setApplicationDate(new \DateTime());
        $application->setFee($this->countSubeventsFee($user->getRoles(), $subevents));
        $application->setMaturityDate($this->countMaturityDate());
        $application->setState($this->getApplicationState($application));
        $application->setVariableSymbol($this->generateVariableSymbol());
        $application->setCreatedBy($createdBy);
        $application->setValidFrom(new \DateTime());
        $this->applicationRepository->save($application);

        $application->setApplicationId($application->getId());
        $this->applicationRepository->save($application);

        return $application;
    }

    /**
     * @return VariableSymbol
     * @throws \App\Model\Settings\SettingsException
     * @throws \Throwable
     */
    private function generateVariableSymbol(): VariableSymbol
    {
        $variableSymbolCode = $this->settingsRepository->getValue(Settings::VARIABLE_SYMBOL_CODE);

        $variableSymbol = new VariableSymbol();
        $this->variableSymbolRepository->save($variableSymbol);

        $variableSymbolText = $variableSymbolCode . str_pad(strval($variableSymbol->getId()), 6, '0', STR_PAD_LEFT);

        $variableSymbol->setVariableSymbol($variableSymbolText);
        $this->variableSymbolRepository->save($variableSymbol);

        return $variableSymbol;
    }

    /**
     * Vypočítá datum splatnosti podle zvolené metody.
     * @return \DateTime|null
     * @throws \App\Model\Settings\SettingsException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    private function countMaturityDate()
    {
        switch ($this->settingsRepository->getValue(Settings::MATURITY_TYPE)) {
            case MaturityType::DATE:
                return $this->settingsRepository->getDateValue(Settings::MATURITY_DATE);

            case MaturityType::DAYS:
                return (new \DateTime())->modify('+' . $this->settingsRepository->getIntValue(Settings::MATURITY_DAYS) . ' days');

            case MaturityType::WORK_DAYS:
                $workDays = $this->settingsRepository->getIntValue(Settings::MATURITY_WORK_DAYS);
                $date = new \DateTime();

                for ($i = 0; $i < $workDays;) {
                    $date->modify('+1 days');
                    $holidays = Yasumi::create('CzechRepublic', $date->format('Y'));

                    if ($holidays->isWorkingDay($date))
                        $i++;
                }

                return $date;
        }
        return NULL;
    }

    /**
     * Vypočítá poplatek za role.
     * @param Collection $roles
     * @return int
     */
    private function countRolesFee(Collection $roles)
    {
        $fee = 0;

        foreach ($roles as $role) {
            if ($role->getFee() === 0)
                return 0;
            elseif ($role->getFee() > 0)
                $fee += $role->getFee();
        }

        return $fee;
    }

    /**
     * Vypočítá poplatek za podakce přihlášky.
     * @param Collection|Role[] $roles
     * @param Collection|Subevent[] $subevents
     * @return int
     */
    private function countSubeventsFee(Collection $roles, Collection $subevents)
    {
        $fee = 0;

        foreach ($roles as $role) {
            if ($role->getFee() === 0)
                return 0;
            elseif ($role->getFee() === NULL) {
                foreach ($subevents as $subevent) {
                    $fee += $subevent->getFee();
                }
                break;
            }
        }

        $discount = $this->discountService->countDiscount($this->subeventRepository->findSubeventsIds($subevents));

        return $fee - $discount;
    }

    /**
     * @param Application $application
     * @return string
     */
    private function getApplicationState(Application $application)
    {
        if ($application->getState() == ApplicationState::CANCELED)
            return ApplicationState::CANCELED;

        if ($application->getState() == ApplicationState::CANCELED_NOT_PAID)
            return ApplicationState::CANCELED_NOT_PAID;

        if ($application->getFee() == 0)
            return ApplicationState::PAID_FREE;
        elseif ($application->getPaymentDate())
            return ApplicationState::PAID;
        else
            return ApplicationState::WAITING_FOR_PAYMENT;
    }

    /**
     * Vrací stav přihlášky jako text.
     * @param Application $application
     * @return string
     */
    public function getStateText(Application $application): string
    {
        $state = $this->translator->translate('common.application_state.' . $application->getState());

        if ($application->getState() == ApplicationState::PAID)
            $state .= ' (' . $application->getPaymentDate()->format(Helpers::DATE_FORMAT) . ')';

        return $state;
    }

    /**
     * Může uživatel upravovat role?
     * @param User $user
     * @return bool
     * @throws \App\Model\Settings\SettingsException
     */
    public function isAllowedEditRegistration(User $user)
    {
        return !$user->isInRole($this->roleRepository->findBySystemName(Role::NONREGISTERED))
            && !$user->hasPaidAnyApplication()
            && $this->settingsRepository->getDateValue(Settings::EDIT_REGISTRATION_TO) >= (new \DateTime())->setTime(0, 0);
    }

    /**
     * Je uživateli povoleno upravit nebo zrušit přihlášku?
     * @param Application $application
     * @return bool
     * @throws \App\Model\Settings\SettingsException
     */
    public function isAllowedEditApplication(Application $application)
    {
        return $application->getType() == Application::SUBEVENTS && !$application->isCanceled()
            && $application->getState() != ApplicationState::PAID
            && $this->settingsRepository->getDateValue(Settings::EDIT_REGISTRATION_TO) >= (new \DateTime())->setTime(0, 0);
    }

    /**
     * Může uživatel dodatečně přidávat podakce?
     * @param User $user
     * @return bool
     * @throws \App\Model\Settings\SettingsException
     * @throws \Throwable
     */
    public function isAllowedAddApplication(User $user)
    {
        return $user->hasPaidEveryApplication()
            && $this->settingsRepository->getBoolValue(Settings::IS_ALLOWED_ADD_SUBEVENTS_AFTER_PAYMENT)
            && $this->settingsRepository->getDateValue(Settings::EDIT_REGISTRATION_TO) >= (new \DateTime())->setTime(0, 0);
    }

    /**
     * Může uživatel upravovat vlastní pole přihlášky?
     * @return bool
     * @throws \App\Model\Settings\SettingsException
     */
    public function isAllowedEditCustomInputs()
    {
        return $this->settingsRepository->getDateValue(Settings::EDIT_CUSTOM_INPUTS_TO) >= (new \DateTime())->setTime(0, 0);
    }
}
