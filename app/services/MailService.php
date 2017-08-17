<?php

namespace App\Services;

use App\Mailing\TextMail;
use App\Model\ACL\RoleRepository;
use App\Model\Mailing\Mail;
use App\Model\Mailing\MailRepository;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsRepository;
use App\Model\User\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Nette;
use Ublaboo\Mailing\MailFactory;


/**
 * Služba pro rozesílání e-mailů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class MailService extends Nette\Object
{
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


    /**
     * MailService constructor.
     * @param MailFactory $mailFactory
     * @param SettingsRepository $settingsRepository
     * @param MailRepository $mailRepository
     * @param UserRepository $userRepository
     * @param RoleRepository $roleRepository
     */
    public function __construct(MailFactory $mailFactory, SettingsRepository $settingsRepository,
                                MailRepository $mailRepository, UserRepository $userRepository,
                                RoleRepository $roleRepository)
    {
        $this->mailFactory = $mailFactory;
        $this->settingsRepository = $settingsRepository;
        $this->mailRepository = $mailRepository;
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
    }

    /**
     * Rozešle e-mail.
     * @param $rolesIds
     * @param $copy
     * @param $subject
     * @param $text
     */
    public function sendMail($rolesIds, $usersIds, $copy, $subject, $text)
    {
        $recipients = [];

        foreach ($this->userRepository->findAllApprovedInRoles($rolesIds) as $user) {
            if (!in_array($user, $recipients))
                $recipients[] = $user;
        }

        $users = $this->userRepository->findUsersByIds($usersIds);
        foreach ($users as $user) {
            if (!in_array($user, $recipients))
                $recipients[] = $user;
        }

        $params = [
            'fromEmail' => $this->settingsRepository->getValue(Settings::SEMINAR_EMAIL),
            'fromName' => $this->settingsRepository->getValue(Settings::SEMINAR_NAME),
            'recipients' => $recipients,
            'copy' => $copy,
            'subject' => $subject,
            'text' => $text
        ];

        $mail = $this->mailFactory->createByType(TextMail::class, $params);
        $mail->send();

        $mailLog = new Mail();
        $mailLog->setRecipientRoles($this->roleRepository->findRolesByIds($rolesIds));
        $mailLog->setRecipientUsers($users);
        $mailLog->setSubject($subject);
        $mailLog->setText($text);
        $mailLog->setDatetime(new \DateTime());
        $this->mailRepository->save($mailLog);
    }
}
