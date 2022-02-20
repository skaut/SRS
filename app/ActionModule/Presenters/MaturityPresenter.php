<?php

declare(strict_types=1);

namespace App\ActionModule\Presenters;

use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Acl\Role;
use App\Model\Enums\ApplicationState;
use App\Model\Mailing\Template;
use App\Model\Mailing\TemplateVariable;
use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Settings\Queries\SettingIntValueQuery;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Settings;
use App\Model\User\Repositories\UserRepository;
use App\Services\ApplicationService;
use App\Services\IMailService;
use App\Services\QueryBus;
use App\Utils\Helpers;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Nette\Application\Responses\TextResponse;
use Throwable;
use Ublaboo\Mailing\Exception\MailingMailCreationException;

/**
 * Presenter obsluhující kontrolu splatnosti přihlášek.
 */
class MaturityPresenter extends ActionBasePresenter
{
    /** @inject */
    public QueryBus $queryBus;

    /** @inject */
    public EntityManagerInterface $em;

    /** @inject */
    public UserRepository $userRepository;

    /** @inject */
    public RoleRepository $roleRepository;

    /** @inject */
    public IMailService $mailService;

    /** @inject */
    public ApplicationService $applicationService;

    /**
     * Zruší přihlášky po splatnosti.
     *
     * @throws Throwable
     */
    public function actionCancelApplications(): void
    {
        $cancelRegistration = $this->queryBus->handle(new SettingIntValueQuery(Settings::CANCEL_REGISTRATION_AFTER_MATURITY));
        if ($cancelRegistration !== null) {
            $cancelRegistrationDate = (new DateTimeImmutable())->setTime(0, 0)->modify('-' . $cancelRegistration . ' days');
        } else {
            return;
        }

        foreach ($this->userRepository->findAllWithWaitingForPaymentApplication() as $user) {
            $this->em->transactional(function () use ($user, $cancelRegistrationDate): void {
                // odhlášení účastníků s nezaplacnou přihláškou rolí
                foreach ($user->getWaitingForPaymentRolesApplications() as $application) {
                    $maturityDate = $application->getMaturityDate();

                    if ($maturityDate !== null && $cancelRegistrationDate > $maturityDate) {
                        $this->applicationService->cancelRegistration($user, ApplicationState::CANCELED_NOT_PAID, null);

                        return;
                    }
                }

                // zrušení nezaplacených přihlášek podakcí
                $subeventsApplicationCanceled = false;
                foreach ($user->getWaitingForPaymentSubeventsApplications() as $application) {
                    $maturityDate = $application->getMaturityDate();

                    if ($maturityDate !== null && $cancelRegistrationDate > $maturityDate) {
                        $this->applicationService->cancelSubeventsApplication($application, ApplicationState::CANCELED_NOT_PAID, null);
                        $subeventsApplicationCanceled = true;
                    }
                }

                // pokud účastníkovi nezbyde žádná podakce, je třeba odebrat i roli s cenou podle podakcí, případně jej odhlásit
                if ($subeventsApplicationCanceled && $user->getSubevents()->isEmpty()) {
                    $newRoles = $user->getRoles()->filter(static function (Role $role) {
                        return $role->getFee() !== null;
                    });
                    if ($newRoles->isEmpty()) {
                        $this->applicationService->cancelRegistration($user, ApplicationState::CANCELED_NOT_PAID, null);
                    } else {
                        $this->applicationService->updateRoles($user, $newRoles, null);
                    }
                }
            });
        }

        $response = new TextResponse(null);
        $this->sendResponse($response);
    }

    /**
     * Rozešle přípomínky splatnosti.
     *
     * @throws Throwable
     */
    public function actionSendReminders(): void
    {
        $maturityReminder = $this->queryBus->handle(new SettingIntValueQuery(Settings::MATURITY_REMINDER));
        if ($maturityReminder !== null) {
            $maturityReminderDate = (new DateTimeImmutable())->setTime(0, 0)->modify('+' . $maturityReminder . ' days');
        } else {
            return;
        }

        foreach ($this->userRepository->findAllWithWaitingForPaymentApplication() as $user) {
            foreach ($user->getWaitingForPaymentApplications() as $application) {
                $maturityDate = $application->getMaturityDate();

                if ($maturityReminderDate == $maturityDate) {
                    $this->mailService->sendMailFromTemplate(new ArrayCollection([$application->getUser()]), null, Template::MATURITY_REMINDER, [
                        TemplateVariable::SEMINAR_NAME => $this->queryBus->handle(new SettingStringValueQuery(Settings::SEMINAR_NAME)),
                        TemplateVariable::APPLICATION_MATURITY => $maturityDate->format(Helpers::DATE_FORMAT),
                    ]);
                }
            }
        }

        $response = new TextResponse(null);
        $this->sendResponse($response);
    }
}
