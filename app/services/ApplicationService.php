<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\Enums\ApplicationState;
use App\Model\Enums\MaturityType;
use App\Model\Enums\PaymentState;
use App\Model\Enums\PaymentType;
use App\Model\Mailing\Template;
use App\Model\Mailing\TemplateVariable;
use App\Model\Payment\Payment;
use App\Model\Payment\PaymentRepository;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
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
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Kdyby\Translation\Translator;
use Nette;
use Ublaboo\Mailing\Exception\MailingException;
use Ublaboo\Mailing\Exception\MailingMailCreationException;
use Yasumi\Yasumi;
use const STR_PAD_LEFT;
use function abs;
use function array_diff;
use function implode;
use function str_pad;
use function strval;

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

    /** @var UserService */
    private $userService;

    /** @var Translator */
    private $translator;

    /** @var PaymentRepository */
    private $paymentRepository;


    public function __construct(
        SettingsRepository $settingsRepository,
        ApplicationRepository $applicationRepository,
        UserRepository $userRepository,
        DiscountRepository $discountRepository,
        RoleRepository $roleRepository,
        SubeventRepository $subeventRepository,
        DiscountService $discountService,
        VariableSymbolRepository $variableSymbolRepository,
        ProgramService $programService,
        MailService $mailService,
        UserService $userService,
        Translator $translator,
        PaymentRepository $paymentRepository
    ) {
        $this->settingsRepository       = $settingsRepository;
        $this->applicationRepository    = $applicationRepository;
        $this->userRepository           = $userRepository;
        $this->discountRepository       = $discountRepository;
        $this->roleRepository           = $roleRepository;
        $this->subeventRepository       = $subeventRepository;
        $this->discountService          = $discountService;
        $this->variableSymbolRepository = $variableSymbolRepository;
        $this->programService           = $programService;
        $this->mailService              = $mailService;
        $this->userService              = $userService;
        $this->translator               = $translator;
        $this->paymentRepository        = $paymentRepository;
    }

    /**
     * Zaregistruje uživatele (vyplnění přihlášky / přidání role v administraci).
     * @param Collection|Role[]     $roles
     * @param Collection|Subevent[] $subevents
     * @throws \Throwable
     */
    public function register(
        User $user,
        Collection $roles,
        Collection $subevents,
        User $createdBy,
        bool $approve = false
    ) : void {
        $rolesApplication     = new RolesApplication();
        $subeventsApplication = new SubeventsApplication();

        $this->applicationRepository->getEntityManager()->transactional(function ($em) use ($user, $roles, $subevents, $createdBy, $approve, &$rolesApplication, &$subeventsApplication) : void {
            $rolesApplication     = $this->createRolesApplication($user, $roles, $createdBy, $approve);
            $subeventsApplication = $this->createSubeventsApplication($user, $subevents, $createdBy);

            $this->programService->updateUserPrograms($user);
            $this->updateUserPaymentInfo($user);
        });

        $applicatonMaturity        = '-';
        $applicationFee            = '0';
        $applicationVariableSymbol = '-';

        if ($rolesApplication->getFee() > 0 && $subeventsApplication->getFee() > 0) {
            if ($rolesApplication->getMaturityDate()) {
                $applicatonMaturity = $rolesApplication->getMaturityDateText();
            }
            $applicationFee            = $rolesApplication->getFee() . ', ' . $subeventsApplication->getFee();
            $applicationVariableSymbol = $rolesApplication->getVariableSymbolText() . ', '
                . $subeventsApplication->getVariableSymbolText();
        } elseif ($rolesApplication->getFee() > 0) {
            if ($rolesApplication->getMaturityDate()) {
                $applicatonMaturity = $rolesApplication->getMaturityDateText();
            }
            $applicationFee            = $rolesApplication->getFee();
            $applicationVariableSymbol = $rolesApplication->getVariableSymbolText();
        } elseif ($subeventsApplication->getFee() > 0) {
            if ($subeventsApplication->getMaturityDate()) {
                $applicatonMaturity = $subeventsApplication->getMaturityDateText();
            }
            $applicationFee            = $subeventsApplication->getFee();
            $applicationVariableSymbol = $subeventsApplication->getVariableSymbolText();
        }

        $editRegistrationToText = $this->settingsRepository->getDateValueText(Settings::EDIT_REGISTRATION_TO);

        $this->mailService->sendMailFromTemplate($user, '', Template::REGISTRATION, [
            TemplateVariable::SEMINAR_NAME => $this->settingsRepository->getValue(Settings::SEMINAR_NAME),
            TemplateVariable::EDIT_REGISTRATION_TO => $editRegistrationToText ?? '-',
            TemplateVariable::APPLICATION_MATURITY => $applicatonMaturity,
            TemplateVariable::APPLICATION_FEE => $applicationFee,
            TemplateVariable::APPLICATION_VARIABLE_SYMBOL => $applicationVariableSymbol,
            TemplateVariable::BANK_ACCOUNT => $this->settingsRepository->getValue(Settings::ACCOUNT_NUMBER),
        ]);
    }

    /**
     * Změní role uživatele.
     * @param Collection|Role[] $roles
     * @throws SettingsException
     * @throws \Throwable
     * @throws MailingException
     * @throws MailingMailCreationException
     */
    public function updateRoles(User $user, Collection $roles, ?User $createdBy, bool $approve = false) : void
    {
        $oldRoles = clone $user->getRoles();

        //pokud se role nezmenily, nic se neprovede
        if ($roles->count() === $oldRoles->count()) {
            $rolesArray    = $roles->map(function (Role $role) {
                return $role->getId();
            })->toArray();
            $oldRolesArray = $oldRoles->map(function (Role $role) {
                return $role->getId();
            })->toArray();

            if (array_diff($rolesArray, $oldRolesArray) === array_diff($oldRolesArray, $rolesArray)) {
                return;
            }
        }

        $this->applicationRepository->getEntityManager()->transactional(function ($em) use ($user, $roles, $createdBy, $approve, $oldRoles) : void {
            if ($oldRoles->contains($this->roleRepository->findBySystemName(Role::NONREGISTERED))) {
                $this->createRolesApplication($user, $roles, $createdBy, $approve);
                $this->createSubeventsApplication($user, new ArrayCollection([$this->subeventRepository->findImplicit()]), $createdBy);
            } else {
                $this->incrementRolesOccupancy($roles);

                $user->setRoles($roles);
                $this->userRepository->save($user);

                if ($roles->forAll(function (int $key, Role $role) {
                    return $role->isApprovedAfterRegistration();
                })) {
                    $user->setApproved(true);
                } elseif (! $approve && $roles->exists(function (int $key, Role $role) use ($oldRoles) {
                    return ! $role->isApprovedAfterRegistration() && ! $oldRoles->contains($role);
                })) {
                    $user->setApproved(false);
                }

                foreach ($user->getNotCanceledApplications() as $application) {
                    if ($application->getType() === Application::ROLES) {
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

                        if ($application->getFee() !== $fee) {
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

                $this->decrementRolesOccupancy($user->getRolesApplication()->getRoles());
            }

            $this->programService->updateUserPrograms($user);
            $this->updateUserPaymentInfo($user);
        });

        $this->mailService->sendMailFromTemplate($user, '', Template::ROLES_CHANGED, [
            TemplateVariable::SEMINAR_NAME => $this->settingsRepository->getValue(Settings::SEMINAR_NAME),
            TemplateVariable::USERS_ROLES => implode(', ', $roles->map(function (Role $role) {
                return $role->getName();
            })->toArray()),
        ]);
    }

    /**
     * Zruší registraci uživatele na seminář.
     * @throws SettingsException
     * @throws \Throwable
     * @throws MailingException
     * @throws MailingMailCreationException
     */
    public function cancelRegistration(User $user, string $state, ?User $createdBy) : void
    {
        $this->applicationRepository->getEntityManager()->transactional(function ($em) use ($user, $state, $createdBy) : void {
            $user->setApproved(true);
            $user->getRoles()->clear();
            $user->setRolesApplicationDate(null);
            $user->addRole($this->roleRepository->findBySystemName(Role::NONREGISTERED));
            $this->userRepository->save($user);

            foreach ($user->getNotCanceledApplications() as $application) {
                $newApplication = clone $application;
                $newApplication->setState($state);
                $newApplication->setCreatedBy($createdBy);
                $newApplication->setValidFrom(new \DateTime());

                if ($newApplication->getPayment() !== null) {
                    if ($newApplication->getPayment()->getPairedValidApplications()->count() === 1) {
                        $newApplication->getPayment()->setState(PaymentState::NOT_PAIRED_CANCELED);
                    }
                    $newApplication->setPayment(null);
                }

                $this->applicationRepository->save($newApplication);

                $application->setValidTo(new \DateTime());
                $this->applicationRepository->save($application);

                if ($application instanceof RolesApplication) {
                    $this->decrementRolesOccupancy($application->getRoles());
                } elseif ($application instanceof SubeventsApplication) {
                    $this->decrementSubeventsOccupancy($application->getSubevents());
                }
            }

            $this->userRepository->save($user);

            $this->programService->updateUserPrograms($user);
            $this->updateUserPaymentInfo($user);
        });

        $this->mailService->sendMailFromTemplate($user, '', Template::REGISTRATION_CANCELED, [
            TemplateVariable::SEMINAR_NAME => $this->settingsRepository->getValue(Settings::SEMINAR_NAME),
        ]);
    }

    /**
     * Vytvoří novou přihlášku na podakce.
     * @param Collection|Subevent[] $subevents
     * @throws \Throwable
     */
    public function addSubeventsApplication(User $user, Collection $subevents, User $createdBy) : void
    {
        $this->applicationRepository->getEntityManager()->transactional(function ($em) use ($user, $subevents, $createdBy) : void {
            $this->incrementSubeventsOccupancy($subevents);

            $this->createSubeventsApplication($user, $subevents, $createdBy);

            $this->programService->updateUserPrograms($user);
            $this->updateUserPaymentInfo($user);
        });

        $this->mailService->sendMailFromTemplate($user, '', Template::SUBEVENTS_CHANGED, [
            TemplateVariable::SEMINAR_NAME => $this->settingsRepository->getValue(Settings::SEMINAR_NAME),
            TemplateVariable::USERS_SUBEVENTS => $user->getSubeventsText(),
        ]);
    }

    /**
     * Aktualizuje podakce přihlášky.
     * @param Collection|Subevent[] $subevents
     * @throws SettingsException
     * @throws \Throwable
     * @throws MailingException
     * @throws MailingMailCreationException
     */
    public function updateSubeventsApplication(SubeventsApplication $application, Collection $subevents, User $createdBy) : void
    {
        if (! $application->isValid()) {
            return;
        }

        $oldSubevents = clone $application->getSubevents();

        //pokud se podakce nezmenily, nic se neprovede
        if ($subevents->count() === $oldSubevents->count()) {
            $subeventsArray    = $subevents->map(function (Subevent $subevent) {
                return $subevent->getId();
            })->toArray();
            $oldSubeventsArray = $oldSubevents->map(function (Subevent $subevent) {
                return $subevent->getId();
            })->toArray();

            if (array_diff($subeventsArray, $oldSubeventsArray) === array_diff($oldSubeventsArray, $subeventsArray)) {
                return;
            }
        }

        $this->applicationRepository->getEntityManager()->transactional(function ($em) use ($application, $subevents, $createdBy) : void {
            $this->incrementSubeventsOccupancy($subevents);

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
            $this->updateUserPaymentInfo($user);

            $this->decrementSubeventsOccupancy($application->getSubevents());
        });

        $this->mailService->sendMailFromTemplate($application->getUser(), '', Template::SUBEVENTS_CHANGED, [
            TemplateVariable::SEMINAR_NAME => $this->settingsRepository->getValue(Settings::SEMINAR_NAME),
            TemplateVariable::USERS_SUBEVENTS => $application->getUser()->getSubeventsText(),
        ]);
    }

    /**
     * Zruší přihlášku na podakce.
     * @throws SettingsException
     * @throws \Throwable
     * @throws MailingException
     * @throws MailingMailCreationException
     */
    public function cancelSubeventsApplication(SubeventsApplication $application, string $state, ?User $createdBy) : void
    {
        if (! $application->isValid()) {
            return;
        }

        $this->applicationRepository->getEntityManager()->transactional(function ($em) use ($application, $state, $createdBy) : void {
            $user = $application->getUser();

            $newApplication = clone $application;
            $newApplication->setState($state);
            $newApplication->setCreatedBy($createdBy);
            $newApplication->setValidFrom(new \DateTime());

            if ($newApplication->getPayment() !== null) {
                if ($newApplication->getPayment()->getPairedValidApplications()->count() === 1) {
                    $newApplication->getPayment()->setState(PaymentState::NOT_PAIRED_CANCELED);
                }
                $newApplication->setPayment(null);
            }

            $this->applicationRepository->save($newApplication);

            $application->setValidTo(new \DateTime());
            $this->applicationRepository->save($application);

            $this->programService->updateUserPrograms($user);
            $this->updateUserPaymentInfo($user);

            $this->decrementSubeventsOccupancy($application->getSubevents());
        });

        $this->mailService->sendMailFromTemplate($application->getUser(), '', Template::SUBEVENTS_CHANGED, [
            TemplateVariable::SEMINAR_NAME => $this->settingsRepository->getValue(Settings::SEMINAR_NAME),
            TemplateVariable::USERS_SUBEVENTS => $application->getUser()->getSubeventsText(),
        ]);
    }

    /**
     * Aktualizuje stav platby.
     * @throws \Throwable
     */
    public function updateApplicationPayment(
        Application $application,
        string $variableSymbol,
        ?string $paymentMethod,
        ?\DateTime $paymentDate,
        ?\DateTime $incomeProofPrintedDate,
        ?\DateTime $maturityDate,
        ?User $createdBy
    ) : void {
        $oldVariableSymbol         = $application->getVariableSymbolText();
        $oldPaymentMethod          = $application->getPaymentMethod();
        $oldPaymentDate            = $application->getPaymentDate();
        $oldIncomeProofPrintedDate = $application->getIncomeProofPrintedDate();
        $oldMaturityDate           = $application->getMaturityDate();

        //pokud neni zmena, nic se neprovede
        if ($variableSymbol === $oldVariableSymbol && $paymentMethod === $oldPaymentMethod //todo zakazat zmenu VS
            && $paymentDate === $oldPaymentDate && $incomeProofPrintedDate === $oldIncomeProofPrintedDate
            && $maturityDate === $oldMaturityDate) {
            return;
        }

        $this->applicationRepository->getEntityManager()->transactional(function ($em) use (
            $application,
            $variableSymbol,
            $paymentMethod,
            $paymentDate,
            $incomeProofPrintedDate,
            $maturityDate,
            $createdBy
        ) : void {
            $user = $application->getUser();

            $newApplication = clone $application;

            if ($application->getVariableSymbolText() !== $variableSymbol) {
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
            $this->updateUserPaymentInfo($user);
        });

        if ($paymentDate === null || $oldPaymentDate !== null) {
            return;
        }

        $this->mailService->sendMailFromTemplate($application->getUser(), '', Template::PAYMENT_CONFIRMED, [
            TemplateVariable::SEMINAR_NAME => $this->settingsRepository->getValue(Settings::SEMINAR_NAME),
            TemplateVariable::APPLICATION_SUBEVENTS => $application->getSubeventsText(),
        ]);
    }

    public function createPayment(\DateTime $date, float $ammount, string $variableSymbol, ?string $transactionId, ?string $accountNumber, ?string $accountName, ?string $message, ?User $createdBy = null) : void
    {
        $this->applicationRepository->getEntityManager()->transactional(function () use ($date, $ammount, $variableSymbol, $transactionId, $accountNumber, $accountName, $message, $createdBy) : void {
            $pairedApplication = $this->applicationRepository->findValidByVariableSymbol($variableSymbol);

            $payment = new Payment();

            $payment->setDate($date);
            $payment->setAmmount($ammount);
            $payment->setVariableSymbol($variableSymbol);
            $payment->setTransactionId($transactionId);
            $payment->setAccountNumber($accountNumber);
            $payment->setAccountName($accountName);
            $payment->setMessage($message);

            if ($pairedApplication) {
                if ($pairedApplication->getState() === ApplicationState::PAID || $pairedApplication->getState() === ApplicationState::PAID_FREE) {
                    $payment->setState(PaymentState::NOT_PAIRED_PAID);
                } elseif (abs($pairedApplication->getFee() - $ammount) >= 0.01) {
                    $payment->setState(PaymentState::NOT_PAIRED_FEE);
                } else {
                    $payment->setState(PaymentState::PAIRED_AUTO);
                    $pairedApplication->setPayment($payment);
                    $this->updateApplicationPayment($pairedApplication, $variableSymbol, PaymentType::BANK, $date, null, null, $createdBy);
                }
            } else {
                $payment->setState(PaymentState::NOT_PAIRED_VS);
            }

            $this->paymentRepository->save($payment);
        });
    }

    public function createPaymentManual(\DateTime $date, float $ammount, string $variableSymbol, User $createdBy) : void
    {
        $this->createPayment($date, $ammount, $variableSymbol, null, null, null, null, $createdBy);
    }

    /**
     * @param Collection|Application[] $pairedApplications
     * @throws \Throwable
     */
    public function updatePayment(Payment $payment, \DateTime $date, float $ammount, string $variableSymbol, Collection $pairedApplications, User $createdBy) : void
    {
        $this->applicationRepository->getEntityManager()->transactional(function () use ($payment, $date, $ammount, $variableSymbol, $pairedApplications, $createdBy) : void {
            $oldPairedApplications = clone $payment->getPairedValidApplications();
            $newPairedApplications = clone $pairedApplications;

            $modified = false;

            foreach ($oldPairedApplications as $pairedApplication) {
                if (! $newPairedApplications->contains($pairedApplication)) {
                    $pairedApplication->setPayment(null);
                    $this->updateApplicationPayment($pairedApplication, $pairedApplication->getVariableSymbolText(), null, null, null, $pairedApplication->getMaturityDate(), $createdBy);
                    $modified = true;
                }
            }
            foreach ($newPairedApplications as $pairedApplication) {
                if (! $oldPairedApplications->contains($pairedApplication)) {
                    $pairedApplication->setPayment($payment);
                    $this->updateApplicationPayment($pairedApplication, $pairedApplication->getVariableSymbolText(), PaymentType::BANK, $date, null, $pairedApplication->getMaturityDate(), $createdBy);
                    $modified = true;
                }
            }

            if ($modified) {
                if ($pairedApplications->isEmpty()) {
                    $payment->setState(PaymentState::NOT_PAIRED);
                } else {
                    $payment->setState(PaymentState::PAIRED_MANUAL);
                }
            }

            $this->paymentRepository->save($payment);
        });
    }

    public function removePayment(Payment $payment, User $createdBy) : void
    {
        $this->applicationRepository->getEntityManager()->transactional(function () use ($payment, $createdBy) : void {
            foreach ($payment->getPairedValidApplications() as $pairedApplication) {
                $this->updateApplicationPayment($pairedApplication, $pairedApplication->getVariableSymbolText(), null, null, null, $pairedApplication->getMaturityDate(), $createdBy);
            }

            $this->paymentRepository->remove($payment);
        });
    }

    /**
     * Vrací stav přihlášky jako text.
     */
    public function getStateText(Application $application) : string
    {
        $state = $this->translator->translate('common.application_state.' . $application->getState());

        if ($application->getState() === ApplicationState::PAID) {
            $state .= ' (' . $application->getPaymentDate()->format(Helpers::DATE_FORMAT) . ')';
        }

        return $state;
    }

    /**
     * Může uživatel upravovat role?
     * @throws SettingsException
     * @throws \Throwable
     */
    public function isAllowedEditRegistration(User $user) : bool
    {
        return ! $user->isInRole($this->roleRepository->findBySystemName(Role::NONREGISTERED))
            && ! $user->hasPaidAnyApplication()
            && $this->settingsRepository->getDateValue(Settings::EDIT_REGISTRATION_TO) >= (new \DateTime())->setTime(0, 0);
    }

    /**
     * Je uživateli povoleno upravit nebo zrušit přihlášku?
     * @throws SettingsException
     * @throws \Throwable
     */
    public function isAllowedEditApplication(Application $application) : bool
    {
        return $application->getType() === Application::SUBEVENTS && ! $application->isCanceled()
            && $application->getState() !== ApplicationState::PAID
            && $this->settingsRepository->getDateValue(Settings::EDIT_REGISTRATION_TO) >= (new \DateTime())->setTime(0, 0);
    }

    /**
     * Může uživatel dodatečně přidávat podakce?
     * @throws SettingsException
     * @throws \Throwable
     */
    public function isAllowedAddApplication(User $user) : bool
    {
        return $user->hasPaidEveryApplication()
            && $this->settingsRepository->getBoolValue(Settings::IS_ALLOWED_ADD_SUBEVENTS_AFTER_PAYMENT)
            && $this->settingsRepository->getDateValue(Settings::EDIT_REGISTRATION_TO) >= (new \DateTime())->setTime(0, 0);
    }

    /**
     * Může uživatel upravovat vlastní pole přihlášky?
     * @throws SettingsException
     * @throws \Throwable
     */
    public function isAllowedEditCustomInputs() : bool
    {
        return $this->settingsRepository->getDateValue(Settings::EDIT_CUSTOM_INPUTS_TO) >= (new \DateTime())->setTime(0, 0);
    }

    /**
     * @param Collection|Role[] $roles
     * @throws SettingsException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    private function createRolesApplication(User $user, Collection $roles, User $createdBy, bool $approve = false) : RolesApplication
    {
        if (! $user->isInRole($this->roleRepository->findBySystemName(Role::NONREGISTERED))) {
            throw new \InvalidArgumentException('User is already registered.');
        }

        $this->incrementRolesOccupancy($roles);

        $user->setApproved(true);
        if (! $approve && $roles->exists(function (int $key, Role $role) {
            return ! $role->isApprovedAfterRegistration();
        })) {
            $user->setApproved(false);
        }

        $user->setRoles($roles);
        $user->setRolesApplicationDate(new \DateTime());
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
     * @param Collection|Subevent[] $subevents
     * @throws SettingsException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    private function createSubeventsApplication(
        User $user,
        Collection $subevents,
        User $createdBy
    ) : SubeventsApplication {
        $this->incrementSubeventsOccupancy($subevents);

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
     * @throws SettingsException
     * @throws \Throwable
     */
    private function generateVariableSymbol() : VariableSymbol
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
     * @throws SettingsException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    private function countMaturityDate() : ?\DateTime
    {
        switch ($this->settingsRepository->getValue(Settings::MATURITY_TYPE)) {
            case MaturityType::DATE:
                return $this->settingsRepository->getDateValue(Settings::MATURITY_DATE);

            case MaturityType::DAYS:
                return (new \DateTime())->modify('+' . $this->settingsRepository->getIntValue(Settings::MATURITY_DAYS) . ' days');

            case MaturityType::WORK_DAYS:
                $workDays = $this->settingsRepository->getIntValue(Settings::MATURITY_WORK_DAYS);
                $date     = new \DateTime();

                for ($i = 0; $i < $workDays;) {
                    $date->modify('+1 days');
                    $holidays = Yasumi::create('CzechRepublic', $date->format('Y'));

                    if (! $holidays->isWorkingDay($date)) {
                        continue;
                    }

                    $i++;
                }

                return $date;
        }
        return null;
    }

    /**
     * Vypočítá poplatek za role.
     * @param Collection|Role[] $roles
     */
    private function countRolesFee(Collection $roles) : int
    {
        $fee = 0;

        foreach ($roles as $role) {
            if ($role->getFee() === 0) {
                return 0;
            } elseif ($role->getFee() > 0) {
                $fee += $role->getFee();
            }
        }

        return $fee;
    }

    /**
     * Vypočítá poplatek za podakce přihlášky.
     * @param Collection|Role[]     $roles
     * @param Collection|Subevent[] $subevents
     */
    private function countSubeventsFee(Collection $roles, Collection $subevents) : int
    {
        $fee = 0;

        foreach ($roles as $role) {
            if ($role->getFee() === 0) {
                return 0;
            }

            if ($role->getFee() === null) {
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
     * Určí stav přihlášky.
     */
    private function getApplicationState(Application $application) : string
    {
        if ($application->getState() === ApplicationState::CANCELED) {
            return ApplicationState::CANCELED;
        }

        if ($application->getState() === ApplicationState::CANCELED_NOT_PAID) {
            return ApplicationState::CANCELED_NOT_PAID;
        }

        if ($application->getFee() === 0) {
            return ApplicationState::PAID_FREE;
        }

        if ($application->getPaymentDate()) {
            return ApplicationState::PAID;
        }

        return ApplicationState::WAITING_FOR_PAYMENT;
    }

    /**
     * Zvýší obsazenost rolí.
     * @param Collection|Role[] $roles
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function incrementRolesOccupancy(Collection $roles) : void
    {
        foreach ($roles as $role) {
            $this->roleRepository->incrementOccupancy($role);
            $this->roleRepository->save($role);
        }
    }

    /**
     * Sníží obsazenost rolí.
     * @param Collection|Role[] $roles
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function decrementRolesOccupancy(Collection $roles) : void
    {
        foreach ($roles as $role) {
            $this->roleRepository->decrementOccupancy($role);
            $this->roleRepository->save($role);
        }
    }

    /**
     * Zvýší obsazenost podakcí.
     * @param Collection|Subevent[] $subevents
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function incrementSubeventsOccupancy(Collection $subevents) : void
    {
        foreach ($subevents as $subevent) {
            $this->subeventRepository->incrementOccupancy($subevent);
            $this->subeventRepository->save($subevent);
        }
    }

    /**
     * Sníží obsazenost podakcí.
     * @param Collection|Subevent[] $subevents
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function decrementSubeventsOccupancy(Collection $subevents) : void
    {
        foreach ($subevents as $subevent) {
            $this->subeventRepository->decrementOccupancy($subevent);
            $this->subeventRepository->save($subevent);
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function updateUserPaymentInfo(User $user) : void
    {
        $fee = 0;
        foreach ($user->getNotCanceledApplications() as $application) {
            $fee += $application->getFee();
        }
        $user->setFee($fee);

        $feeRemaining = 0;
        foreach ($user->getWaitingForPaymentApplications() as $application) {
            $feeRemaining += $application->getFee();
        }
        $user->setFeeRemaining($feeRemaining);

        $user->setPaymentMethod($this->userService->getPaymentMethod($user));

        $maxDate = null;
        foreach ($user->getValidApplications() as $application) {
            if ($maxDate >= $application->getPaymentDate()) {
                continue;
            }
            $maxDate = $application->getPaymentDate();
        }
        $user->setLastPaymentDate($maxDate);

        $this->userRepository->save($user);
    }
}
