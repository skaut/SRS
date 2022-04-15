<?php

declare(strict_types=1);

namespace App\Model\Program\Commands\Handlers;

use App\Mailing\SrsMail;
use App\Mailing\SrsMailData;
use App\Model\Mailing\Recipient;
use App\Model\Mailing\Repositories\MailRepository;
use App\Model\Program\Commands\SendQueue;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Settings;
use App\Services\QueryBus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class SendQueueHandler implements MessageHandlerInterface
{
    private QueryBus $queryBus;

    private MailRepository $mailRepository;

    public function __construct(QueryBus $queryBus, MailRepository $mailRepository)
    {
        $this->queryBus = $queryBus;
        $this->mailRepository = $mailRepository;
    }

    public function __invoke(SendQueue $command): void
    {
//        foreach ( as $mail) {
//            $recipients = [];
//
//            foreach ($this->userRepository->findAllApprovedInRoles($this->roleRepository->findRolesIds($recipientsRoles)) as $user) {
//                $recipient = Recipient::createFromUser($user);
//                if (! in_array($recipient, $recipients)) {
//                    $recipients[] = $recipient;
//                }
//            }
//
//            foreach ($this->userRepository->findAllWithSubevents($this->subeventRepository->findSubeventsIds($recipientsSubevents)) as $user) {
//                $recipient = Recipient::createFromUser($user);
//                if (! in_array($recipient, $recipients)) {
//                    $recipients[] = $recipient;
//                }
//            }
//
//            foreach ($recipientsUsers as $user) {
//                $recipient = Recipient::createFromUser($user);
//                if (! in_array($recipient, $recipients)) {
//                    $recipients[] = $recipient;
//                }
//            }
//
//            foreach ($recipientEmails as $email) {
//                $recipient = new Recipient($email);
//                if (! in_array($recipient, $recipients)) {
//                    $recipients[] = $recipient;
//                }
//            }
//
//            $from = new Recipient($this->queryBus->handle(new SettingStringValueQuery(Settings::SEMINAR_EMAIL)), $this->queryBus->handle(new SettingStringValueQuery(Settings::SEMINAR_NAME)));
//
//            $messageData = new SrsMailData($from, $recipients, $subject, $text);
//            $mail1        = $this->mailFactory->createByType(SrsMail::class, $messageData);
//            $mail1->send();
//
//            $mail->setSent(true);
//            $this->mailRepository->save($mail);
//        }
    }
}
