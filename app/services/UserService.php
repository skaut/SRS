<?php

namespace App\Services;

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
class UserService
{
    use Nette\SmartObject;

    /** @var Translator */
    private $translator;

    /** @var UserRepository */
    private $userRepository;

    /** @var ApplicationRepository */
    private $applicationRepository;


    /**
     * UserService constructor.
     * @param Translator $translator
     * @param UserRepository $userRepository
     * @param ApplicationRepository $applicationRepository
     */
    public function __construct(Translator $translator, UserRepository $userRepository,
                                ApplicationRepository $applicationRepository)
    {
        $this->translator = $translator;
        $this->userRepository = $userRepository;
        $this->applicationRepository = $applicationRepository;
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
