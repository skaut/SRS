<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\Enums\PaymentType;
use App\Model\Mailing\Template;
use App\Model\Mailing\TemplateVariable;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Settings;
use App\Model\User\Events\UserUpdatedEvent;
use App\Model\User\Repositories\UserRepository;
use App\Model\User\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Nette;
use Nette\Localization\Translator;

/**
 * Služba pro správu uživatelů.
 */
class UserService
{
    use Nette\SmartObject;

    private QueryBus $queryBus;

    private EventBus $eventBus;

    private Translator $translator;

    private UserRepository $userRepository;

    private MailService $mailService;

    private EntityManagerInterface $em;

    public function __construct(
        QueryBus $queryBus,
        EventBus $eventBus,
        Translator $translator,
        UserRepository $userRepository,
        MailService $mailService,
        EntityManagerInterface $em
    ) {
        $this->queryBus       = $queryBus;
        $this->eventBus       = $eventBus;
        $this->translator     = $translator;
        $this->userRepository = $userRepository;
        $this->mailService    = $mailService;
        $this->em             = $em;
    }

    /**
     * Vrací informaci o členství jako text.
     */
    public function getMembershipText(User $user): string
    {
        if ($user->getUnit() !== null) {
            return $user->getUnit();
        }

        if ($user->isMember()) {
            return $this->translator->translate('admin.users.users_membership_no');
        }

        if ($user->isExternalLector()) {
            return $this->translator->translate('admin.users.users_membership_external');
        }

        return $this->translator->translate('admin.users.users_membership_not_connected');
    }

    /**
     * Vrací platební metodu uživatele.
     */
    public function getPaymentMethod(User $user): ?string
    {
        $paymentMethod = null;

        foreach ($user->getNotCanceledApplications() as $application) {
            $currentPaymentMethod = $application->getPaymentMethod();
            if ($currentPaymentMethod) {
                if (! $paymentMethod) {
                    $paymentMethod = $currentPaymentMethod;
                } elseif ($paymentMethod !== $currentPaymentMethod) {
                    return PaymentType::MIXED;
                }
            }
        }

        if ($paymentMethod) {
            return $paymentMethod;
        }

        return null;
    }

    /**
     * Nastaví registraci uživatele jako schválenou nebo nechválenou.
     */
    public function setApproved(User $user, bool $approved): void
    {
        $this->em->wrapInTransaction(function () use ($user, $approved): void {
            $approvedOld = $user->isApproved();
            $user->setApproved($approved);
            $this->userRepository->save($user);

            $this->eventBus->handle(new UserUpdatedEvent($user, $approvedOld));

            if ($approved) {
                $this->mailService->sendMailFromTemplate(new ArrayCollection([$user]), null, Template::REGISTRATION_APPROVED, [
                    TemplateVariable::SEMINAR_NAME => $this->queryBus->handle(new SettingStringValueQuery(Settings::SEMINAR_NAME)),
                ]);
            }
        });
    }
}
