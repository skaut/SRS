<?php

declare(strict_types=1);

namespace App\Services;

use App\Mailing\TextMail;
use App\Model\ACL\RoleRepository;
use App\Model\Mailing\Mail;
use App\Model\Mailing\MailRepository;
use App\Model\Mailing\TemplateRepository;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Model\Settings\SettingsRepository;
use App\Model\User\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Kdyby\Translation\Translator;
use Nette;
use Ublaboo\Mailing\Exception\MailingException;
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

    /** @var MailFactory */
    private $mailFactory;

    /** @var SettingsRepository */
    private $settingsRepository;

    /** @var MailRepository */
    private $mailRepository;

    /** @var UserRepository */
    private $userRepository;

    /** @var RoleRepository */
    private $roleRepository;

    /** @var TemplateRepository */
    private $templateRepository;

    /** @var Translator */
    private $translator;


    public function __construct(
        MailFactory $mailFactory,
        SettingsRepository $settingsRepository,
        MailRepository $mailRepository,
        UserRepository $userRepository,
        RoleRepository $roleRepository,
        TemplateRepository $templateRepository,
        Translator $translator
    ) {
        $this->mailFactory        = $mailFactory;
        $this->settingsRepository = $settingsRepository;
        $this->mailRepository     = $mailRepository;
        $this->userRepository     = $userRepository;
        $this->roleRepository     = $roleRepository;
        $this->templateRepository = $templateRepository;
        $this->translator         = $translator;
    }

    /**
     * Rozešle e-mail.
     * @param $recipientsRoles
     * @param $recipientsUsers
     * @param $copy
     * @param $subject
     * @param $text
     * @throws SettingsException
     * @throws \Throwable
     * @throws MailingException
     * @throws MailingMailCreationException
     */
    public function sendMail($recipientsRoles, $recipientsUsers, $copy, $subject, $text, bool $automatic = false) : void
    {
        $recipients = [];

        foreach ($this->userRepository->findAllApprovedInRoles($this->roleRepository->findRolesIds($recipientsRoles)) as $user) {
            if (in_array($user, $recipients)) {
                continue;
            }

            $recipients[] = $user;
        }

        foreach ($recipientsUsers as $user) {
            if (in_array($user, $recipients)) {
                continue;
            }

            $recipients[] = $user;
        }

        $params = [
            'fromEmail' => $this->settingsRepository->getValue(Settings::SEMINAR_EMAIL),
            'fromName' => $this->settingsRepository->getValue(Settings::SEMINAR_NAME),
            'recipients' => $recipients,
            'copy' => $copy,
            'subject' => $subject,
            'text' => $text,
        ];

        $mail = $this->mailFactory->createByType(TextMail::class, $params);
        $mail->send();

        $mailLog = new Mail();
        $mailLog->setRecipientRoles($recipientsRoles);
        $mailLog->setRecipientUsers($recipientsUsers);
        $mailLog->setSubject($subject);
        $mailLog->setText($text);
        $mailLog->setDatetime(new \DateTime());
        $mailLog->setAutomatic($automatic);
        $this->mailRepository->save($mailLog);
    }

    /**
     * Rozešle e-mail podle šablony.
     * @param $recipientUser
     * @param $copy
     * @param $type
     * @param $parameters
     * @throws SettingsException
     * @throws \Throwable
     * @throws MailingException
     * @throws MailingMailCreationException
     */
    public function sendMailFromTemplate($recipientUser, $copy, $type, $parameters, bool $automatic = true) : void
    {
        $template = $this->templateRepository->findByType($type);

        if (! $template->isActive()) {
            return;
        }

        $recipientsRoles = new ArrayCollection();
        $recipientsUsers = new ArrayCollection();

        if ($template->isSendToUser()) {
            $recipientsUsers->add($recipientUser);
        }

        if ($template->isSendToOrganizer()) {
            $copy = $this->settingsRepository->getValue(Settings::SEMINAR_EMAIL);
        }

        $subject = $template->getSubject();
        $text    = $template->getText();

        foreach ($template->getVariables() as $variable) {
            $variableName = '%' . $this->translator->translate('common.mailing.variable_name.' . $variable->getName()) . '%';
            $value        = $parameters[$variable->getName()];

            $subject = str_replace($variableName, $value, $subject);
            $text    = str_replace($variableName, $value, $text);
        }

        $this->sendMail($recipientsRoles, $recipientsUsers, $copy, $subject, $text, $automatic);
    }
}
