<?php

namespace App\Services;


use App\Mailing\TextMail;
use App\Model\ACL\RoleRepository;
use App\Model\Mailing\Mail;
use App\Model\Mailing\MailRepository;
use App\Model\Mailing\MailToRoles;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsRepository;
use App\Model\User\UserRepository;
use Nette;
use Ublaboo\Mailing\MailFactory;

class MailService extends Nette\Object
{
    /**
     * @var MailFactory
     */
    private $mailFactory;

    /**
     * @var SettingsRepository
     */
    private $settingsRepository;

    /**
     * @var MailRepository
     */
    private $mailRepository;

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var RoleRepository
     */
    private $roleRepository;


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

    public function sendMailToRoles($rolesIds, $copy, $subject, $text) {
        $users = $this->userRepository->findAllApprovedInRoles($rolesIds);

        $params = [
            'fromEmail' => $this->settingsRepository->getValue(Settings::SEMINAR_EMAIL),
            'fromName' => $this->settingsRepository->getValue(Settings::SEMINAR_NAME),
            'recipients' => $users,
            'copy' => $copy,
            'subject' => $subject,
            'text' => $text
        ];

        $mail = $this->mailFactory->createByType(TextMail::class, $params);
        $mail->send();

        $mailLog = new MailToRoles();
        $mailLog->setRecipientRoles($this->roleRepository->findRolesByIds($rolesIds));
        $mailLog->setSubject($subject);
        $mailLog->setDatetime(new \DateTime());
        $this->mailRepository->save($mailLog);
    }
}