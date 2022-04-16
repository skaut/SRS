<?php

declare(strict_types=1);

namespace App\Model\Program\Commands\Handlers;

use App\Model\Mailing\Repositories\MailBatchRepository;
use App\Model\Program\Commands\SaveBatch;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class SaveBatchHandler implements MessageHandlerInterface
{
    private MailBatchRepository $mailBatchRepository;

    public function __construct(MailBatchRepository $mailBatchRepository)
    {
        $this->mailBatchRepository = $mailBatchRepository;
    }

    public function __invoke(SaveBatch $command): void
    {
        $this->mailBatchRepository->save($command->getBatch());
    }
}
