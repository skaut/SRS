<?php

declare(strict_types=1);

namespace App\Services;

use App\Mailing\SrsMail;
use App\Mailing\SrsMailData;
use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Acl\Role;
use App\Model\Mailing\Mail;
use App\Model\Mailing\Recipient;
use App\Model\Mailing\Repositories\MailRepository;
use App\Model\Mailing\Repositories\TemplateRepository;
use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Settings;
use App\Model\Structure\Repositories\SubeventRepository;
use App\Model\Structure\Subevent;
use App\Model\User\Repositories\UserRepository;
use App\Model\User\User;
use DateTimeImmutable;
use Doctrine\Common\Collections\Collection;
use Nette;
use Nette\Localization\ITranslator;
use Throwable;
use Ublaboo\Mailing\Exception\MailingMailCreationException;
use Ublaboo\Mailing\MailFactory;

use function in_array;
use function str_replace;

/**
 * Služba pro rozesílání e-mailů.
 */
class MailService implements IMailService
{
    use Nette\SmartObject;

    private QueryBus $queryBus;

    private MailFactory $mailFactory;

    private MailRepository $mailRepository;

    private UserRepository $userRepository;

    private RoleRepository $roleRepository;

    private SubeventRepository $subeventRepository;

    private TemplateRepository $templateRepository;

    private ITranslator $translator;

    public function __construct(
        QueryBus $queryBus,
        MailFactory $mailFactory,
        MailRepository $mailRepository,
        UserRepository $userRepository,
        RoleRepository $roleRepository,
        SubeventRepository $subeventRepository,
        TemplateRepository $templateRepository,
        ITranslator $translator
    ) {
        $this->queryBus           = $queryBus;
        $this->mailFactory        = $mailFactory;
        $this->mailRepository     = $mailRepository;
        $this->userRepository     = $userRepository;
        $this->roleRepository     = $roleRepository;
        $this->subeventRepository = $subeventRepository;
        $this->templateRepository = $templateRepository;
        $this->translator         = $translator;
    }

    /**
     * Rozešle e-mail.
     *
     * @param Collection<int, Role>|null     $recipientsRoles
     * @param Collection<int, Subevent>|null $recipientsSubevents
     * @param Collection<int, User>|null     $recipientsUsers
     * @param Collection<int, string>|null   $recipientEmails
     *
     * @throws SettingsItemNotFoundException
     * @throws Throwable
     * @throws MailingMailCreationException
     */
    public function sendMail(?Collection $recipientsRoles, ?Collection $recipientsSubevents, ?Collection $recipientsUsers, ?Collection $recipientEmails, string $subject, string $text, bool $automatic = false): void
    {
        $recipients = [];

        if ($recipientsRoles !== null) {
            foreach ($this->userRepository->findAllApprovedInRoles($this->roleRepository->findRolesIds($recipientsRoles)) as $user) {
                $recipient = Recipient::createFromUser($user);
                if (! in_array($recipient, $recipients)) {
                    $recipients[] = $recipient;
                }
            }
        }

        if ($recipientsSubevents !== null) {
            foreach ($this->userRepository->findAllWithSubevents($this->subeventRepository->findSubeventsIds($recipientsSubevents)) as $user) {
                $recipient = Recipient::createFromUser($user);
                if (! in_array($recipient, $recipients)) {
                    $recipients[] = $recipient;
                }
            }
        }

        if ($recipientsUsers !== null) {
            foreach ($recipientsUsers as $user) {
                $recipient = Recipient::createFromUser($user);
                if (! in_array($recipient, $recipients)) {
                    $recipients[] = $recipient;
                }
            }
        }

        if ($recipientEmails !== null) {
            foreach ($recipientEmails as $email) {
                $recipient = new Recipient($email);
                if (! in_array($recipient, $recipients)) {
                    $recipients[] = $recipient;
                }
            }
        }

        $from = new Recipient($this->queryBus->handle(new SettingStringValueQuery(Settings::SEMINAR_EMAIL)), $this->queryBus->handle(new SettingStringValueQuery(Settings::SEMINAR_NAME)));

        $messageData = new SrsMailData($from, $recipients, $subject, $text);
        $mail        = $this->mailFactory->createByType(SrsMail::class, $messageData);
        $mail->send();

        $mailLog = new Mail();

        if ($recipientsRoles !== null) {
            $mailLog->setRecipientRoles($recipientsRoles);
        }

        if ($recipientsSubevents !== null) {
            $mailLog->setRecipientSubevents($recipientsSubevents);
        }

        if ($recipientsUsers !== null) {
            $mailLog->setRecipientUsers($recipientsUsers);
        }

        $mailLog->setSubject($subject);
        $mailLog->setText($text);
        $mailLog->setDatetime(new DateTimeImmutable());
        $mailLog->setAutomatic($automatic);
        $this->mailRepository->save($mailLog);
    }

    /**
     * Rozešle e-mail podle šablony.
     *
     * @param Collection<int, User>|null   $recipientsUsers
     * @param Collection<int, string>|null $recipientsEmails
     * @param string[]                     $parameters
     *
     * @throws MailingMailCreationException
     * @throws SettingsItemNotFoundException
     * @throws Throwable
     */
    public function sendMailFromTemplate(?Collection $recipientsUsers, ?Collection $recipientsEmails, string $type, array $parameters): void
    {
        $template = $this->templateRepository->findByType($type);

        if (! $template->isActive()) {
            return;
        }

        $subject = $template->getSubject();
        $text    = $template->getText();

        foreach ($template->getVariables() as $variable) {
            $variableName = '%' . $this->translator->translate('common.mailing.variable_name.' . $variable->getName()) . '%';
            $value        = $parameters[$variable->getName()];

            $subject = str_replace($variableName, $value, $subject);
            $text    = str_replace($variableName, $value, $text);
        }

        $this->sendMail(null, null, $recipientsUsers, $recipientsEmails, $subject, $text, true);
    }
}
