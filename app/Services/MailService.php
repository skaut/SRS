<?php

declare(strict_types=1);

namespace App\Services;

use App\Mailing\SrsMail;
use App\Mailing\SrsMailData;
use App\Model\Acl\Role;
use App\Model\Acl\RoleRepository;
use App\Model\Mailing\Mail;
use App\Model\Mailing\MailRepository;
use App\Model\Mailing\Recipient;
use App\Model\Mailing\TemplateRepository;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Model\Structure\Subevent;
use App\Model\Structure\SubeventRepository;
use App\Model\User\User;
use App\Model\User\UserRepository;
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
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class MailService
{
    use Nette\SmartObject;

    private MailFactory $mailFactory;

    private SettingsService $settingsService;

    private MailRepository $mailRepository;

    private UserRepository $userRepository;

    private RoleRepository $roleRepository;

    private SubeventRepository $subeventRepository;

    private TemplateRepository $templateRepository;

    private ITranslator $translator;

    public function __construct(
        MailFactory $mailFactory,
        SettingsService $settingsService,
        MailRepository $mailRepository,
        UserRepository $userRepository,
        RoleRepository $roleRepository,
        SubeventRepository $subeventRepository,
        TemplateRepository $templateRepository,
        ITranslator $translator
    ) {
        $this->mailFactory        = $mailFactory;
        $this->settingsService    = $settingsService;
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
     * @param Collection|Role[]|null     $recipientsRoles
     * @param Collection|Subevent[]|null $recipientsSubevents
     * @param Collection|User[]|null     $recipientsUsers
     * @param Collection|string[]|null   $recipientEmails
     *
     * @throws SettingsException
     * @throws Throwable
     * @throws MailingMailCreationException
     */
    public function sendMail(?Collection $recipientsRoles, ?Collection $recipientsSubevents, ?Collection $recipientsUsers, ?Collection $recipientEmails, string $subject, string $text, bool $automatic = false) : void
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

        $from = new Recipient($this->settingsService->getValue(Settings::SEMINAR_EMAIL), $this->settingsService->getValue(Settings::SEMINAR_NAME));

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
     * @param Collection|User[]|null   $recipientsUsers
     * @param Collection|string[]|null $recipientsEmails
     * @param string[]                 $parameters
     *
     * @throws MailingMailCreationException
     * @throws SettingsException
     * @throws Throwable
     */
    public function sendMailFromTemplate(?Collection $recipientsUsers, ?Collection $recipientsEmails, string $type, array $parameters) : void
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
