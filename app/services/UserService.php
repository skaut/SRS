<?php

namespace App\Services;

use App\Model\Enums\ApplicationState;
use App\Model\Mailing\Template;
use App\Model\Mailing\TemplateVariable;
use App\Model\Settings\Settings;
use App\Model\User\ApplicationRepository;
use App\Model\User\User;
use App\Model\User\UserRepository;
use Kdyby\Translation\Translator;
use Nette;


/**
 * Služba pro správu uživatelů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class UserService extends Nette\Object
{
    /** @var Translator */
    private $translator;

    /** @var ProgramService */
    private $programService;

    /** @var UserRepository */
    private $userRepository;

    /** @var ApplicationRepository */
    private $applicationRepository;


    /**
     * UserService constructor.
     * @param Translator $translator
     * @param ProgramService $programService
     * @param UserRepository $userRepository
     * @param ApplicationRepository $applicationRepository
     */
    public function __construct(Translator $translator, ProgramService $programService, UserRepository $userRepository,
                                ApplicationRepository $applicationRepository)
    {
        $this->translator = $translator;
        $this->programService = $programService;
        $this->userRepository = $userRepository;
        $this->applicationRepository = $applicationRepository;
    }

    /**
     * Změní role uživatele.
     * @param User $user
     * @param $roles
     * @param bool $approved
     */
    public function changeRoles(User $user, $roles, $approved = FALSE)
    {
        if (!$approved && $user->isApproved()) {
            foreach ($roles as $role) {
                if (!$role->isApprovedAfterRegistration() && !$user->isInRole($role)) {
                    $user->setApproved(FALSE);
                    break;
                }
            }
        }

        $user->setRoles($roles);

        $this->userRepository->save($user);

        foreach ($user->getApplications() as $application) {
            $fee = $this->applicationService->countFee($roles, $application->getSubevents(), $application->isFirst());

            $application->setFee($fee);
            $application->setState($fee == 0 || $application->getPaymentDate()
                ? ApplicationState::PAID
                : ApplicationState::WAITING_FOR_PAYMENT);

            $this->applicationRepository->save($application);
        }

        $this->programService->updateUserPrograms($user);

        //zaslání potvrzovacího e-mailu
        $rolesNames = [];
        foreach ($this->user->getRoles() as $role) {
            $rolesNames[] = $role->getName();
        }

        $this->mailService->sendMailFromTemplate($this->user, '', Template::ROLES_CHANGED, [
            TemplateVariable::SEMINAR_NAME => $this->settingsRepository->getValue(Settings::SEMINAR_NAME),
            TemplateVariable::USERS_ROLES => implode(', ', $rolesNames)
        ]);
    }

    public function cancelRegistration()
    {
        //TODO
    }

    /**
     * @param User $user
     * @return string
     */
    public function getMembershipText(User $user)
    {
        if ($user->getUnit() !== NULL)
            return $user->getUnit();
        else if ($user->isMember())
            return $this->translator->translate('admin.users.users_membership_no');
        else if ($user->isExternal())
            return $this->translator->translate('admin.users.users_membership_external');
        else
            return $this->translator->translate('admin.users.users_membership_not_connected');
    }

    /**
     * @param User $user
     * @return string
     */
    public function getPaymentMethodText(User $user)
    {
        $paymentMethod = NULL;

        foreach ($user->getApplications() as $application) {
            $currentPaymentMethod = $application->getPaymentMethod();
            if ($currentPaymentMethod) {
                if ($paymentMethod === NULL) {
                    $paymentMethod = $currentPaymentMethod;
                    continue;
                }
                if ($paymentMethod != $currentPaymentMethod) {
                    return $this->translator->translate('common.payment.mixed');
                }
            }
        }

        if ($paymentMethod)
            return $this->translator->translate('common.payment.' . $paymentMethod);

        return NULL;
    }
}
