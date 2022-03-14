<?php

declare(strict_types=1);

namespace App\Model\Program\Commands\Handlers;

use App\Model\Mailing\Repositories\MailRepository;
use App\Model\Program\Commands\SendQueue;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class SendQueueHandler implements MessageHandlerInterface
{
    private EntityManagerInterface $em;

    private MailRepository $mailRepository;

    public function __construct(EntityManagerInterface $em, MailRepository $mailRepository)
    {
        $this->em             = $em;
        $this->mailRepository = $mailRepository;
    }

    public function __invoke(SendQueue $command): void
    {
    }
}
