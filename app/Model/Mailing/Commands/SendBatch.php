<?php

declare(strict_types=1);

namespace App\Model\Program\Commands;

use App\Model\Mailing\MailBatch;

class SendBatch
{
    private MailBatch $batch;

    public function __construct(MailBatch $batch)
    {
        $this->batch = $batch;
    }

    public function getBatch(): MailBatch
    {
        return $this->batch;
    }
}
