<?php

declare(strict_types=1);

namespace App\Model\Settings\Queries\Handlers;

use App\Model\Enums\ProgramRegistrationType;
use App\Model\Settings\Queries\IsAllowedRegisterProgramsQuery;
use App\Model\Settings\Settings;
use App\Services\SettingsService;
use DateTimeImmutable;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class IsAllowedRegisterProgramsQueryHandler implements MessageHandlerInterface
{
    private SettingsService $settingsService;

    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    public function __invoke(IsAllowedRegisterProgramsQuery $query): bool
    {
        $registerProgramsType = $this->settingsService->getValue(Settings::REGISTER_PROGRAMS_TYPE);

        if ($registerProgramsType === ProgramRegistrationType::ALLOWED) {
            return true;
        } elseif ($registerProgramsType === ProgramRegistrationType::ALLOWED_FROM_TO) {
            $registerProgramsFrom = $this->settingsService->getDateTimeValue(Settings::REGISTER_PROGRAMS_FROM);
            $registerProgramsTo   = $this->settingsService->getDateTimeValue(Settings::REGISTER_PROGRAMS_TO);

            return ($registerProgramsFrom === null || $registerProgramsFrom <= new DateTimeImmutable())
                && ($registerProgramsTo === null || $registerProgramsTo >= new DateTimeImmutable());
        }

        return false;
    }
}
