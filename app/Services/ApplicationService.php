<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Acl\Role;
use App\Model\Application\Application;
use App\Model\Application\Events\ApplicationUpdatedEvent;
use App\Model\Application\IncomeProof;
use App\Model\Application\Repositories\ApplicationRepository;
use App\Model\Application\Repositories\IncomeProofRepository;
use App\Model\Application\Repositories\VariableSymbolRepository;
use App\Model\Application\RolesApplication;
use App\Model\Application\SubeventsApplication;
use App\Model\Application\VariableSymbol;
use App\Model\Enums\ApplicationState;
use App\Model\Enums\MaturityType;
use App\Model\Enums\PaymentState;
use App\Model\Enums\PaymentType;
use App\Model\Mailing\Commands\CreateTemplateMail;
use App\Model\Mailing\Template;
use App\Model\Mailing\TemplateVariable;
use App\Model\Payment\Payment;
use App\Model\Payment\Repositories\PaymentRepository;
use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Settings\Queries\SettingBoolValueQuery;
use App\Model\Settings\Queries\SettingDateValueAsTextQuery;
use App\Model\Settings\Queries\SettingDateValueQuery;
use App\Model\Settings\Queries\SettingIntValueQuery;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Settings;
use App\Model\Structure\Repositories\SubeventRepository;
use App\Model\Structure\Subevent;
use App\Model\User\Repositories\UserRepository;
use App\Model\User\User;
use App\Utils\Helpers;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use InvalidArgumentException;
use Nette;
use Nette\Localization\Translator;
use ReflectionException;
use Throwable;
use Ublaboo\Mailing\Exception\MailingMailCreationException;
use Yasumi\Yasumi;

use function abs;
use function implode;
use function str_pad;
use function strval;

use const STR_PAD_LEFT;

/**
 * Služba pro správu přihlašování na akci.
 */
class ApplicationService
{
    use Nette\SmartObject;

    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
        private readonly EntityManagerInterface $em,
        private readonly ApplicationRepository $applicationRepository,
        private readonly UserRepository $userRepository,
        private readonly AclService $aclService,
        private readonly RoleRepository $roleRepository,
        private readonly SubeventRepository $subeventRepository,
        private readonly DiscountService $discountService,
        private readonly VariableSymbolRepository $variableSymbolRepository,
        private readonly UserService $userService,
        private readonly Translator $translator,
        private readonly PaymentRepository $paymentRepository,
        private readonly IncomeProofRepository $incomeProofRepository,
        private readonly EventBus $eventBus,
    ) {
    }

    /**
     * Zaregistruje uživatele (vyplnění přihlášky / přidání role v administraci).
     *
     * @param Collection<int, Role>     $roles
     * @param Collection<int, Subevent> $subevents
     *
     * @throws Throwable
     */
    public function register(
        User $user,
        Collection $roles,
        Collection $subevents,
        User $createdBy,
        bool $approve = false,
    ): void {
        $rolesApplication     = $this->createRolesApplication($user, $roles, $createdBy, $approve);
        $subeventsApplication = $this->createSubeventsApplication($user, $subevents, $createdBy);

        $this->eventBus->handle(new ApplicationUpdatedEvent($user));
        $this->updateUserPaymentInfo($user);

        $applicatonMaturity        = '-';
        $applicationFee            = '0';
        $applicationVariableSymbol = '-';

        if ($rolesApplication->getFee() > 0 && $subeventsApplication->getFee() > 0) {
            if ($rolesApplication->getMaturityDate()) {
                $applicatonMaturity = $rolesApplication->getMaturityDateText();
            }

            $applicationFee            = $rolesApplication->getFee() . ', ' . $subeventsApplication->getFee();
            $applicationVariableSymbol = $rolesApplication->getVariableSymbolText() . ', ' . $subeventsApplication->getVariableSymbolText();
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

        $editRegistrationToText = $this->queryBus->handle(
            new SettingDateValueAsTextQuery(Settings::EDIT_REGISTRATION_TO),
        );

        $this->commandBus->handle(new CreateTemplateMail(
            new ArrayCollection(
                [$user],
            ),
            null,
            Template::REGISTRATION,
            [
                TemplateVariable::SEMINAR_NAME => $this->queryBus->handle(new SettingStringValueQuery(Settings::SEMINAR_NAME)),
                TemplateVariable::EDIT_REGISTRATION_TO => $editRegistrationToText ?? '-',
                TemplateVariable::APPLICATION_MATURITY => $applicatonMaturity,
                TemplateVariable::APPLICATION_FEE => $applicationFee,
                TemplateVariable::APPLICATION_VARIABLE_SYMBOL => $applicationVariableSymbol,
                TemplateVariable::BANK_ACCOUNT => $this->queryBus->handle(
                    new SettingStringValueQuery(Settings::ACCOUNT_NUMBER),
                ),
            ],
        ));
    }

    /**
     * Změní role uživatele a provede historizaci přihlášky.
     *
     * @param Collection<int, Role> $roles
     *
     * @throws SettingsItemNotFoundException
     * @throws Throwable
     * @throws MailingMailCreationException
     */
    public function updateRoles(User $user, Collection $roles, User|null $createdBy, bool $approve = false): void
    {
        $rolesOld = clone $user->getRoles();

        if (Helpers::collectionsEquals($roles, $rolesOld)) {
            return;
        }

        $this->em->wrapInTransaction(function () use ($user, $roles, $createdBy, $approve, $rolesOld): void {
            if ($rolesOld->contains($this->roleRepository->findBySystemName(Role::NONREGISTERED))) {
                $this->createRolesApplication($user, $roles, $createdBy, $approve);
                $this->createSubeventsApplication(
                    $user,
                    new ArrayCollection(
                        [$this->subeventRepository->findImplicit()],
                    ),
                    $createdBy,
                );
            } else {
                $this->incrementRolesOccupancy($roles);

                $user->setRoles($roles);
                $this->userRepository->save($user);

                if (
                    $roles->forAll(
                        static fn (int $key, Role $role) => $role->isApprovedAfterRegistration()
                    )
                ) {
                    $user->setApproved(true);
                } elseif (
                    ! $approve
                    && $roles->exists(
                        static fn (int $key, Role $role) => ! $role->isApprovedAfterRegistration() && ! $rolesOld->contains($role)
                    )
                ) {
                    $user->setApproved(false);
                }

                foreach ($user->getNotCanceledApplications() as $application) {
                    if ($application instanceof RolesApplication) {
                        $newApplication = clone $application;
                        $newApplication->setRoles($roles);
                        $newApplication->setFee($this->countRolesFee($roles));
                        $newApplication->setState($this->getApplicationState($newApplication));
                        $newApplication->setCreatedBy($createdBy);
                        $newApplication->setValidFrom(new DateTimeImmutable());
                        $this->applicationRepository->save($newApplication);

                        $application->setValidTo(new DateTimeImmutable());
                        $this->applicationRepository->save($application);
                    } else {
                        $fee = $this->countSubeventsFee($roles, $application->getSubevents());

                        if ($application->getFee() !== $fee) {
                            $newApplication = clone $application;
                            $newApplication->setFee($fee);
                            $newApplication->setState($this->getApplicationState($newApplication));
                            $newApplication->setCreatedBy($createdBy);
                            $newApplication->setValidFrom(new DateTimeImmutable());
                            $this->applicationRepository->save($newApplication);

                            $application->setValidTo(new DateTimeImmutable());
                            $this->applicationRepository->save($application);
                        }
                    }
                }

                $this->userRepository->save($user);

                $this->decrementRolesOccupancy($user->getRolesApplication()->getRoles());
            }

            $this->eventBus->handle(new ApplicationUpdatedEvent($user));
            $this->updateUserPaymentInfo($user);
        });

        $this->commandBus->handle(new CreateTemplateMail(
            new ArrayCollection(
                [$user],
            ),
            null,
            Template::ROLES_CHANGED,
            [
                TemplateVariable::SEMINAR_NAME => $this->queryBus->handle(new SettingStringValueQuery(Settings::SEMINAR_NAME)),
                TemplateVariable::USERS_ROLES => implode(', ', $roles->map(static fn (Role $role) => $role->getName())->toArray()),
            ],
        ));
    }

    /**
     * Zruší registraci uživatele na seminář a provede historizaci přihlášky.
     *
     * @throws SettingsItemNotFoundException
     * @throws Throwable
     * @throws MailingMailCreationException
     */
    public function cancelRegistration(User $user, string $state, User|null $createdBy): void
    {
        $this->em->wrapInTransaction(function () use ($user, $state, $createdBy): void {
            $user->setApproved(true);
            $user->getRoles()->clear();
            $user->setRolesApplicationDate(null);
            $user->addRole($this->roleRepository->findBySystemName(Role::NONREGISTERED));
            $this->userRepository->save($user);

            foreach ($user->getNotCanceledApplications() as $application) {
                $newApplication = clone $application;
                $newApplication->setState($state);
                $newApplication->setCreatedBy($createdBy);
                $newApplication->setValidFrom(new DateTimeImmutable());

                if ($newApplication->getPayment() !== null) {
                    if ($newApplication->getPayment()->getPairedValidApplications()->count() === 1) {
                        $newApplication->getPayment()->setState(PaymentState::NOT_PAIRED_CANCELED);
                    }

                    $newApplication->setPayment(null);
                }

                $this->applicationRepository->save($newApplication);

                $application->setValidTo(new DateTimeImmutable());
                $this->applicationRepository->save($application);

                if ($application instanceof RolesApplication) {
                    $this->decrementRolesOccupancy($application->getRoles());
                } elseif ($application instanceof SubeventsApplication) {
                    $this->decrementSubeventsOccupancy($application->getSubevents());
                }
            }

            $this->userRepository->save($user);

            $this->eventBus->handle(new ApplicationUpdatedEvent($user));
            $this->updateUserPaymentInfo($user);
        });

        if ($state === ApplicationState::CANCELED) {
            $this->commandBus->handle(new CreateTemplateMail(
                new ArrayCollection(
                    [$user],
                ),
                null,
                Template::REGISTRATION_CANCELED,
                [
                    TemplateVariable::SEMINAR_NAME => $this->queryBus->handle(
                        new SettingStringValueQuery(Settings::SEMINAR_NAME),
                    ),
                ],
            ));
        } elseif ($state === ApplicationState::CANCELED_NOT_PAID) {
            $this->commandBus->handle(new CreateTemplateMail(
                new ArrayCollection(
                    [$user],
                ),
                null,
                Template::REGISTRATION_CANCELED_NOT_PAID,
                [
                    TemplateVariable::SEMINAR_NAME => $this->queryBus->handle(
                        new SettingStringValueQuery(
                            Settings::SEMINAR_NAME,
                        ),
                    ),
                ],
            ));
        }
    }

    /**
     * Vytvoří novou přihlášku na podakce a provede její historizaci.
     *
     * @param Collection<int, Subevent> $subevents
     *
     * @throws Throwable
     */
    public function addSubeventsApplication(User $user, Collection $subevents, User $createdBy): void
    {
        $this->em->wrapInTransaction(function () use ($user, $subevents, $createdBy): void {
            $this->incrementSubeventsOccupancy($subevents);

            $this->createSubeventsApplication($user, $subevents, $createdBy);

            $this->eventBus->handle(new ApplicationUpdatedEvent($user));
            $this->updateUserPaymentInfo($user);
        });

        $this->commandBus->handle(new CreateTemplateMail(
            new ArrayCollection(
                [$user],
            ),
            null,
            Template::SUBEVENTS_CHANGED,
            [
                TemplateVariable::SEMINAR_NAME => $this->queryBus->handle(
                    new SettingStringValueQuery(Settings::SEMINAR_NAME),
                ),
                TemplateVariable::USERS_SUBEVENTS => $user->getSubeventsText(),
            ],
        ));
    }

    /**
     * Aktualizuje podakce přihlášky a provede její historizaci.
     *
     * @param Collection<int, Subevent> $subevents
     *
     * @throws SettingsItemNotFoundException
     * @throws Throwable
     * @throws MailingMailCreationException
     */
    public function updateSubeventsApplication(SubeventsApplication $application, Collection $subevents, User $createdBy): void
    {
        if (! $application->isValid()) {
            return;
        }

        $subeventsOld = clone $application->getSubevents();

        if (Helpers::collectionsEquals($subevents, $subeventsOld)) {
            return;
        }

        $this->em->wrapInTransaction(function () use ($application, $subevents, $createdBy): void {
            $this->incrementSubeventsOccupancy($subevents);

            $user = $application->getUser();

            $newApplication = clone $application;
            $newApplication->setSubevents($subevents);
            $newApplication->setFee($this->countSubeventsFee($user->getRoles(), $subevents));
            $newApplication->setState($this->getApplicationState($newApplication));
            $newApplication->setCreatedBy($createdBy);
            $newApplication->setValidFrom(new DateTimeImmutable());
            $this->applicationRepository->save($newApplication);

            $application->setValidTo(new DateTimeImmutable());
            $this->applicationRepository->save($application);

            $this->eventBus->handle(new ApplicationUpdatedEvent($user));
            $this->updateUserPaymentInfo($user);

            $this->decrementSubeventsOccupancy($application->getSubevents());
        });

        $this->commandBus->handle(new CreateTemplateMail(
            new ArrayCollection(
                [$application->getUser()],
            ),
            null,
            Template::SUBEVENTS_CHANGED,
            [
                TemplateVariable::SEMINAR_NAME => $this->queryBus->handle(
                    new SettingStringValueQuery(Settings::SEMINAR_NAME),
                ),
                TemplateVariable::USERS_SUBEVENTS => $application->getUser()->getSubeventsText(),
            ],
        ));
    }

    /**
     * Zruší přihlášku na podakce a provede její historizaci.
     *
     * @throws SettingsItemNotFoundException
     * @throws Throwable
     * @throws MailingMailCreationException
     */
    public function cancelSubeventsApplication(SubeventsApplication $application, string $state, User|null $createdBy): void
    {
        if (! $application->isValid()) {
            return;
        }

        $this->em->wrapInTransaction(function () use ($application, $state, $createdBy): void {
            $user = $application->getUser();

            $newApplication = clone $application;
            $newApplication->setState($state);
            $newApplication->setCreatedBy($createdBy);
            $newApplication->setValidFrom(new DateTimeImmutable());

            if ($newApplication->getPayment() !== null) {
                if ($newApplication->getPayment()->getPairedValidApplications()->count() === 1) {
                    $newApplication->getPayment()->setState(PaymentState::NOT_PAIRED_CANCELED);
                }

                $newApplication->setPayment(null);
            }

            $this->applicationRepository->save($newApplication);

            $application->setValidTo(new DateTimeImmutable());
            $this->applicationRepository->save($application);

            $this->eventBus->handle(new ApplicationUpdatedEvent($user));
            $this->updateUserPaymentInfo($user);

            $this->decrementSubeventsOccupancy($application->getSubevents());
        });

        $this->commandBus->handle(new CreateTemplateMail(
            new ArrayCollection(
                [$application->getUser()],
            ),
            null,
            Template::SUBEVENTS_CHANGED,
            [
                TemplateVariable::SEMINAR_NAME => $this->queryBus->handle(
                    new SettingStringValueQuery(Settings::SEMINAR_NAME),
                ),
                TemplateVariable::USERS_SUBEVENTS => $application->getUser()->getSubeventsText(),
            ],
        ));
    }

    /**
     * Aktualizuje stav platby přihlášky a provede její historizaci.
     *
     * @throws Throwable
     */
    public function updateApplicationPayment(
        Application $application,
        string|null $paymentMethod,
        DateTimeImmutable|null $paymentDate,
        DateTimeImmutable|null $maturityDate,
        User|null $createdBy,
    ): void {
        $oldPaymentMethod = $application->getPaymentMethod();
        $oldPaymentDate   = $application->getPaymentDate();
        $oldMaturityDate  = $application->getMaturityDate();

        // pokud neni zmena, nic se neprovede
        if ($paymentMethod === $oldPaymentMethod && $paymentDate == $oldPaymentDate && $maturityDate == $oldMaturityDate) {
            return;
        }

        $this->em->wrapInTransaction(function () use ($application, $paymentMethod, $paymentDate, $maturityDate, $createdBy): void {
            $user = $application->getUser();

            $newApplication = clone $application;
            $newApplication->setPaymentMethod($paymentMethod);
            $newApplication->setPaymentDate($paymentDate);
            $newApplication->setMaturityDate($maturityDate);
            $newApplication->setState($this->getApplicationState($newApplication));
            $newApplication->setCreatedBy($createdBy);
            $newApplication->setValidFrom(new DateTimeImmutable());
            $this->applicationRepository->save($newApplication);

            $application->setValidTo(new DateTimeImmutable());
            $this->applicationRepository->save($application);

            $this->eventBus->handle(new ApplicationUpdatedEvent($user));
            $this->updateUserPaymentInfo($user);
        });

        if ($paymentDate !== null && $oldPaymentDate === null) {
            $this->commandBus->handle(new CreateTemplateMail(
                new ArrayCollection(
                    [
                        $application->getUser(),
                    ],
                ),
                null,
                Template::PAYMENT_CONFIRMED,
                [
                    TemplateVariable::SEMINAR_NAME => $this->queryBus->handle(
                        new SettingStringValueQuery(Settings::SEMINAR_NAME),
                    ),
                    TemplateVariable::APPLICATION_SUBEVENTS => $application->getSubeventsText(),
                ],
            ));
        }
    }

    /**
     * Vytvoří platbu a označí spárované přihlášky jako zaplacené.
     *
     * @throws Throwable
     */
    public function createPayment(
        DateTimeImmutable $date,
        float $amount,
        string|null $variableSymbol,
        string|null $transactionId,
        string|null $accountNumber,
        string|null $accountName,
        string|null $message,
        User|null $createdBy = null,
    ): void {
        $this->em->wrapInTransaction(function () use ($date, $amount, $variableSymbol, $transactionId, $accountNumber, $accountName, $message, $createdBy): void {
            $payment = new Payment();

            $payment->setDate($date);
            $payment->setAmount($amount);
            $payment->setVariableSymbol($variableSymbol);
            $payment->setTransactionId($transactionId);
            $payment->setAccountNumber($accountNumber);
            $payment->setAccountName($accountName);
            $payment->setMessage($message);

            $pairedApplication = $this->applicationRepository->findValidByVariableSymbol($variableSymbol);

            if ($pairedApplication) {
                if (
                    $pairedApplication->getState() === ApplicationState::PAID ||
                    $pairedApplication->getState() === ApplicationState::PAID_FREE
                ) {
                    $payment->setState(PaymentState::NOT_PAIRED_PAID);
                } elseif (
                    $pairedApplication->getState() === ApplicationState::CANCELED ||
                    $pairedApplication->getState() === ApplicationState::CANCELED_NOT_PAID
                ) {
                    $payment->setState(PaymentState::NOT_PAIRED_CANCELED);
                } elseif (abs($pairedApplication->getFee() - $amount) >= 0.01) {
                    $payment->setState(PaymentState::NOT_PAIRED_FEE);
                } else {
                    $payment->setState(PaymentState::PAIRED_AUTO);
                    $pairedApplication->setPayment($payment);
                    $this->updateApplicationPayment($pairedApplication, PaymentType::BANK, $date, $pairedApplication->getMaturityDate(), $createdBy);
                }
            } else {
                $payment->setState(PaymentState::NOT_PAIRED_VS);
            }

            $this->paymentRepository->save($payment);
        });
    }

    /**
     * Vytvoří platbu a označí spárované přihlášky jako zaplacené (bez údajů z banky).
     *
     * @throws Throwable
     */
    public function createPaymentManual(
        DateTimeImmutable $date,
        float $amount,
        string $variableSymbol,
        User $createdBy,
    ): void {
        $this->createPayment($date, $amount, $variableSymbol, null, null, null, null, $createdBy);
    }

    /**
     * Aktualizuje platbu a stav spárovaných přihlášek.
     *
     * @param Collection<int, Application> $pairedApplications
     *
     * @throws Throwable
     */
    public function updatePayment(
        Payment $payment,
        DateTimeImmutable|null $date,
        float|null $amount,
        string|null $variableSymbol,
        Collection $pairedApplications,
        User $createdBy,
    ): void {
        $this->em->wrapInTransaction(function () use ($payment, $date, $amount, $variableSymbol, $pairedApplications, $createdBy): void {
            if ($date !== null) {
                $payment->setDate($date);
            }

            if ($amount !== null) {
                $payment->setAmount($amount);
            }

            if ($variableSymbol !== null) {
                $payment->setVariableSymbol($variableSymbol);
            }

            $oldPairedApplications = clone $payment->getPairedValidApplications();
            $newPairedApplications = clone $pairedApplications;

            $pairedApplicationsModified = false;

            foreach ($oldPairedApplications as $pairedApplication) {
                if (! $newPairedApplications->contains($pairedApplication)) {
                    $pairedApplication->setPayment(null);
                    $this->updateApplicationPayment($pairedApplication, null, null, $pairedApplication->getMaturityDate(), $createdBy);
                    $pairedApplicationsModified = true;
                }
            }

            foreach ($newPairedApplications as $pairedApplication) {
                if (! $oldPairedApplications->contains($pairedApplication)) {
                    $pairedApplication->setPayment($payment);
                    $this->updateApplicationPayment($pairedApplication, PaymentType::BANK, $payment->getDate(), $pairedApplication->getMaturityDate(), $createdBy);
                    $pairedApplicationsModified = true;
                }
            }

            if ($pairedApplicationsModified) {
                if ($pairedApplications->isEmpty()) {
                    $payment->setState(PaymentState::NOT_PAIRED);
                } else {
                    $payment->setState(PaymentState::PAIRED_MANUAL);
                }
            }

            $this->paymentRepository->save($payment);
        });
    }

    /**
     * Odstraní platbu a označí spárované přihlášky jako nezaplacené.
     *
     * @throws Throwable
     */
    public function removePayment(Payment $payment, User $createdBy): void
    {
        $this->em->wrapInTransaction(function () use ($payment, $createdBy): void {
            foreach ($payment->getPairedValidApplications() as $pairedApplication) {
                $this->updateApplicationPayment($pairedApplication, null, null, $pairedApplication->getMaturityDate(), $createdBy);
            }

            $this->paymentRepository->remove($payment);
        });
    }

    /**
     * Vytvoří příjmový doklad a provede historizaci přihlášky.
     */
    public function createIncomeProof(Application $application, User $createdBy): void
    {
        if ($application->getPaymentMethod() !== PaymentType::CASH) {
            return;
        }

        $this->em->wrapInTransaction(function () use ($application, $createdBy): void {
            $incomeProof = new IncomeProof();
            $this->incomeProofRepository->save($incomeProof);

            $newApplication = clone $application;
            $newApplication->setIncomeProof($incomeProof);
            $newApplication->setCreatedBy($createdBy);
            $newApplication->setValidFrom(new DateTimeImmutable());
            $this->applicationRepository->save($newApplication);

            $application->setValidTo(new DateTimeImmutable());
            $this->applicationRepository->save($application);
        });
    }

    /**
     * Vrací stav přihlášky jako text.
     */
    public function getStateText(Application $application): string
    {
        $state = $this->translator->translate('common.application_state.' . $application->getState());

        if ($application->getState() === ApplicationState::PAID) {
            $state .= ' (' . $application->getPaymentDate()->format(Helpers::DATE_FORMAT) . ')';
        }

        return $state;
    }

    /**
     * Může uživatel upravovat role?
     *
     * @throws Throwable
     */
    public function isAllowedEditRegistration(User $user): bool
    {
        return ! $user->isInRole($this->roleRepository->findBySystemName(Role::NONREGISTERED))
            && ! $user->hasPaidAnyApplication()
            && $this->queryBus->handle(
                new SettingDateValueQuery(Settings::EDIT_REGISTRATION_TO),
            ) >= (new DateTimeImmutable())
                ->setTime(0, 0);
    }

    /**
     * Je uživateli povoleno upravit nebo zrušit přihlášku?
     *
     * @throws Throwable
     */
    public function isAllowedEditApplication(Application $application): bool
    {
        return $application instanceof SubeventsApplication
            && ! $application->isCanceled()
            && $application->getState() !== ApplicationState::PAID
            && $this->queryBus->handle(
                new SettingDateValueQuery(Settings::EDIT_REGISTRATION_TO),
            ) >= (new DateTimeImmutable())
                ->setTime(0, 0);
    }

    /**
     * Může uživatel dodatečně přidávat podakce?
     *
     * @throws Throwable
     */
    public function isAllowedAddApplication(User $user): bool
    {
        return ! $user->isInRole(
            $this->roleRepository->findBySystemName(Role::NONREGISTERED),
        )
            && $user->hasPaidEveryApplication()
            && $this->queryBus->handle(
                new SettingBoolValueQuery(Settings::IS_ALLOWED_ADD_SUBEVENTS_AFTER_PAYMENT),
            )
            && $this->queryBus->handle(
                new SettingDateValueQuery(Settings::EDIT_REGISTRATION_TO),
            ) >= (new DateTimeImmutable())
                ->setTime(0, 0);
    }

    /**
     * Může uživatel upravovat vlastní pole přihlášky?
     *
     * @throws Throwable
     */
    public function isAllowedEditCustomInputs(): bool
    {
        return $this->queryBus->handle(
            new SettingDateValueQuery(Settings::EDIT_CUSTOM_INPUTS_TO),
        ) >= (new DateTimeImmutable())
            ->setTime(0, 0);
    }

    /**
     * @param Collection<int, Role> $roles
     *
     * @throws SettingsItemNotFoundException
     * @throws OptimisticLockException
     * @throws ReflectionException
     * @throws Throwable
     */
    private function createRolesApplication(User $user, Collection $roles, User $createdBy, bool $approve = false): RolesApplication
    {
        if (! $user->isInRole($this->roleRepository->findBySystemName(Role::NONREGISTERED))) {
            throw new InvalidArgumentException('User is already registered.');
        }

        $this->incrementRolesOccupancy($roles);

        $user->setApproved(true);

        if (
            ! $approve && $roles->exists(
                static fn (int $key, Role $role) => ! $role->isApprovedAfterRegistration()
            )
        ) {
            $user->setApproved(false);
        }

        $user->setRoles($roles);
        $user->setRolesApplicationDate(new DateTimeImmutable());
        $this->userRepository->save($user);

        if ($user->getRolesApplication() != null) {
            throw new InvalidArgumentException('User is already registered.');
        }

        $application = new RolesApplication($user);
        $application->setRoles($roles);
        $application->setApplicationDate(new DateTimeImmutable());
        $application->setFee($this->countRolesFee($roles));
        $application->setMaturityDate($this->countMaturityDate());
        $application->setState($this->getApplicationState($application));
        $application->setCreatedBy($createdBy);
        $application->setValidFrom(new DateTimeImmutable());
        $application->setVariableSymbol($this->generateVariableSymbol());
        $this->applicationRepository->save($application);

        $application->setApplicationId($application->getId());
        $this->applicationRepository->save($application);

        return $application;
    }

    /**
     * @param Collection<int, Subevent> $subevents
     *
     * @throws SettingsItemNotFoundException
     * @throws OptimisticLockException
     * @throws ReflectionException
     * @throws Throwable
     */
    private function createSubeventsApplication(
        User $user,
        Collection $subevents,
        User $createdBy,
    ): SubeventsApplication {
        $this->incrementSubeventsOccupancy($subevents);

        $application = new SubeventsApplication($user);
        $application->setSubevents($subevents);
        $application->setApplicationDate(new DateTimeImmutable());
        $application->setFee($this->countSubeventsFee($user->getRoles(), $subevents));
        $application->setMaturityDate($this->countMaturityDate());
        $application->setState($this->getApplicationState($application));
        $application->setCreatedBy($createdBy);
        $application->setValidFrom(new DateTimeImmutable());
        $application->setVariableSymbol($this->generateVariableSymbol());
        $this->applicationRepository->save($application);

        $application->setApplicationId($application->getId());
        $this->applicationRepository->save($application);

        return $application;
    }

    /** @throws Throwable */
    private function generateVariableSymbol(): VariableSymbol
    {
        $variableSymbolCode = $this->queryBus->handle(new SettingStringValueQuery(Settings::VARIABLE_SYMBOL_CODE));

        $variableSymbol = new VariableSymbol();
        $this->variableSymbolRepository->save($variableSymbol);

        $variableSymbolText = $variableSymbolCode . str_pad(strval($variableSymbol->getId()), 5, '0', STR_PAD_LEFT);

        $variableSymbol->setVariableSymbol($variableSymbolText);
        $this->variableSymbolRepository->save($variableSymbol);

        return $variableSymbol;
    }

    /**
     * Vypočítá datum splatnosti podle zvolené metody.
     *
     * @throws Throwable
     */
    private function countMaturityDate(): DateTimeImmutable|null
    {
        switch (
            $this->queryBus->handle(
                new SettingStringValueQuery(Settings::MATURITY_TYPE),
            )
        ) {
            case MaturityType::DATE:
                return $this->queryBus->handle(new SettingDateValueQuery(Settings::MATURITY_DATE));

            case MaturityType::DAYS:
                return (new DateTimeImmutable())->modify('+' . $this->queryBus->handle(new SettingIntValueQuery(Settings::MATURITY_DAYS)) . ' days');

            case MaturityType::WORK_DAYS:
                $workDays = $this->queryBus->handle(new SettingIntValueQuery(Settings::MATURITY_WORK_DAYS));
                $date     = new DateTimeImmutable();

                for ($i = 0; $i < $workDays;) {
                    $date     = $date->modify('+1 days');
                    $holidays = Yasumi::create('CzechRepublic', (int) $date->format('Y'));

                    if ($holidays->isWorkingDay($date)) {
                        $i++;
                    }
                }

                return $date;
        }

        return null;
    }

    /**
     * Vypočítá poplatek za role.
     *
     * @param Collection<int, Role> $roles
     */
    private function countRolesFee(Collection $roles): int
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
     *
     * @param Collection<int, Role>     $roles
     * @param Collection<int, Subevent> $subevents
     */
    private function countSubeventsFee(Collection $roles, Collection $subevents): int
    {
        $fee = 0;

        foreach ($roles as $role) {
            if ($role->getFee() === 0) {
                return 0;
            }
        }

        foreach ($roles as $role) {
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
    private function getApplicationState(Application $application): string
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
     *
     * @param Collection<int, Role> $roles
     */
    private function incrementRolesOccupancy(Collection $roles): void
    {
        foreach ($roles as $role) {
            $this->roleRepository->incrementOccupancy($role);
            $this->aclService->saveRole($role);
        }
    }

    /**
     * Sníží obsazenost rolí.
     *
     * @param Collection<int, Role> $roles
     */
    private function decrementRolesOccupancy(Collection $roles): void
    {
        foreach ($roles as $role) {
            $this->roleRepository->decrementOccupancy($role);
            $this->aclService->saveRole($role);
        }
    }

    /**
     * Zvýší obsazenost podakcí.
     *
     * @param Collection<int, Subevent> $subevents
     */
    private function incrementSubeventsOccupancy(Collection $subevents): void
    {
        foreach ($subevents as $subevent) {
            $this->subeventRepository->incrementOccupancy($subevent);
            $this->subeventRepository->save($subevent);
        }
    }

    /**
     * Sníží obsazenost podakcí.
     *
     * @param Collection<int, Subevent> $subevents
     */
    private function decrementSubeventsOccupancy(Collection $subevents): void
    {
        foreach ($subevents as $subevent) {
            $this->subeventRepository->decrementOccupancy($subevent);
            $this->subeventRepository->save($subevent);
        }
    }

    private function updateUserPaymentInfo(User $user): void
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
            if ($maxDate < $application->getPaymentDate()) {
                $maxDate = $application->getPaymentDate();
            }
        }

        $user->setLastPaymentDate($maxDate);

        $this->userRepository->save($user);
    }
}
