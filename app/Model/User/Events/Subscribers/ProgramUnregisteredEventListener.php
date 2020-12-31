<?php

declare(strict_types=1);

namespace App\Model\User\Events\Subscribers;

use App\Model\Program\Repositories\ProgramApplicationRepository;
use App\Model\User\Events\ProgramUnregisteredEvent;
use App\Services\MailService;
use App\Services\SettingsService;
use eGen\MessageBus\Bus\QueryBus;

class ProgramUnregisteredEventListener
{
    private QueryBus $queryBus;

    private ProgramApplicationRepository $programApplicationRepository;

    private MailService $mailService;

    private SettingsService $settingsService;

    public function __construct(
        QueryBus $queryBus,
        ProgramApplicationRepository $programApplicationRepository,
        MailService $mailService,
        SettingsService $settingsService
    ) {
        $this->queryBus                      = $queryBus;
        $this->programApplicationRepository  = $programApplicationRepository;
        $this->mailService                   = $mailService;
        $this->settingsService               = $settingsService;
    }

    public function __invoke(ProgramUnregisteredEvent $event) : void
    {
        //todo
    }
}
