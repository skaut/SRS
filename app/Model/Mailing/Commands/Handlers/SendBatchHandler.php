<?php

declare(strict_types=1);

namespace App\Model\Program\Commands\Handlers;

use App\Mailing\SrsMail;
use App\Mailing\SrsMailData;
use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Mailing\Recipient;
use App\Model\Mailing\Repositories\MailBatchRepository;
use App\Model\Program\Commands\SendBatch;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Settings;
use App\Model\Structure\Repositories\SubeventRepository;
use App\Model\User\Repositories\UserRepository;
use App\Services\QueryBus;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Ublaboo\Mailing\MailFactory;

use function in_array;

class SendBatchHandler implements MessageHandlerInterface
{
    private QueryBus $queryBus;

    private MailFactory $mailFactory;

    private MailBatchRepository $mailBatchRepository;

    private UserRepository $userRepository;

    private RoleRepository $roleRepository;

    private SubeventRepository $subeventRepository;

    public function __construct(
        QueryBus $queryBus,
        MailFactory $mailFactory,
        MailBatchRepository $mailBatchRepository,
        UserRepository $userRepository,
        RoleRepository $roleRepository,
        SubeventRepository $subeventRepository
    ) {
        $this->queryBus            = $queryBus;
        $this->mailFactory         = $mailFactory;
        $this->mailBatchRepository = $mailBatchRepository;
        $this->userRepository      = $userRepository;
        $this->roleRepository      = $roleRepository;
        $this->subeventRepository  = $subeventRepository;
    }

    public function __invoke(SendBatch $command): void
    {
        $batch = $command->getBatch();

        foreach ($batch->getMails() as $mail) {
            $recipients = [];

            foreach ($this->userRepository->findAllApprovedInRoles($this->roleRepository->findRolesIds($mail->getRecipientRoles())) as $user) {
                $recipient = Recipient::createFromUser($user);
                if (! in_array($recipient, $recipients)) {
                    $recipients[] = $recipient;
                }
            }

            foreach ($this->userRepository->findAllWithSubevents($this->subeventRepository->findSubeventsIds($mail->getRecipientSubevents())) as $user) {
                $recipient = Recipient::createFromUser($user);
                if (! in_array($recipient, $recipients)) {
                    $recipients[] = $recipient;
                }
            }

            foreach ($mail->getRecipientUsers() as $user) {
                $recipient = Recipient::createFromUser($user);
                if (! in_array($recipient, $recipients)) {
                    $recipients[] = $recipient;
                }
            }

            foreach ($mail->getRecipientEmails() as $email) {
                $recipient = new Recipient($email);
                if (! in_array($recipient, $recipients)) {
                    $recipients[] = $recipient;
                }
            }

            $from = new Recipient($this->queryBus->handle(new SettingStringValueQuery(Settings::SEMINAR_EMAIL)), $this->queryBus->handle(new SettingStringValueQuery(Settings::SEMINAR_NAME)));

            $messageData = new SrsMailData($from, $recipients, $mail->getSubject(), $mail->getText());
            $mail1       = $this->mailFactory->createByType(SrsMail::class, $messageData);
            $mail1->send();
        }

        $batch->setSent(true);

        $this->mailBatchRepository->save($batch);
    }
}
