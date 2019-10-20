<?php

declare(strict_types=1);

namespace App\Services;

use App\Mailing\TextMail;
use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\Mailing\Mail;
use App\Model\Mailing\MailRepository;
use App\Model\Mailing\TemplateRepository;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Model\Settings\SettingsRepository;
use App\Model\Structure\Subevent;
use App\Model\Structure\SubeventRepository;
use App\Model\User\User;
use App\Model\User\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    /** @var SubeventRepository */
    private $subeventRepository;

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
        SubeventRepository $subeventRepository,
        TemplateRepository $templateRepository,
        Translator $translator
    ) {
        $this->mailFactory        = $mailFactory;
        $this->settingsRepository = $settingsRepository;
        $this->mailRepository     = $mailRepository;
        $this->userRepository     = $userRepository;
        $this->roleRepository     = $roleRepository;
        $this->subeventRepository = $subeventRepository;
        $this->templateRepository = $templateRepository;
        $this->translator         = $translator;
    }

    /**
     * Rozešle e-mail.
     * @param Collection|Role[]     $recipientsRoles
     * @param Collection|Subevent[] $recipientsSubevents
     * @param Collection|User[]     $recipientsUsers
     * @throws SettingsException
     * @throws \Throwable
     * @throws MailingMailCreationException
     */
    public function sendMail(Collection $recipientsRoles, Collection $recipientsSubevents, Collection $recipientsUsers, string $copy, string $subject, string $text, bool $automatic = false) : void
    {
        $recipients = [];

        foreach ($this->userRepository->findAllApprovedInRoles($this->roleRepository->findRolesIds($recipientsRoles)) as $user) {
            if (in_array($user, $recipients)) {
                continue;
            }
            $recipients[] = $user;
        }

        foreach ($this->userRepository->findAllWithSubevents($this->subeventRepository->findSubeventsIds($recipientsSubevents)) as $user) {
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
        $mailLog->setRecipientSubevents($recipientsSubevents);
        $mailLog->setRecipientUsers($recipientsUsers);
        $mailLog->setSubject($subject);
        $mailLog->setText($text);
        $mailLog->setDatetime(new \DateTime());
        $mailLog->setAutomatic($automatic);
        $this->mailRepository->save($mailLog);
    }

    /**
     * Rozešle e-mail podle šablony.
     * @param string[] $parameters
     * @throws SettingsException
     * @throws \Throwable
     * @throws MailingMailCreationException
     */
    public function sendMailFromTemplate(?User $recipientUser, string $copy, string $type, array $parameters, bool $automatic = true) : void
    {
        $template = $this->templateRepository->findByType($type);

        if (! $template->isActive()) {
            return;
        }

        $recipientsRoles     = new ArrayCollection();
        $recipientsSubevents = new ArrayCollection();
        $recipientsUsers     = new ArrayCollection();

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

        $this->sendMail($recipientsRoles, $recipientsSubevents, $recipientsUsers, $copy, $subject, $text, $automatic);
    }
}
