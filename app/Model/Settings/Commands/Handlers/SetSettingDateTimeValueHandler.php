<?php

declare(strict_types=1);

namespace App\Model\Settings\Commands\Handlers;

use App\Model\Settings\Commands\SetSettingDateTimeValue;
use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Settings\Repositories\SettingsRepository;
use DateTimeImmutable;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class SetSettingDateTimeValueHandler implements MessageHandlerInterface
{
    public function __construct(private SettingsRepository $settingsRepository)
    {
    }

    /**
     * @throws SettingsItemNotFoundException
     */
    public function __invoke(SetSettingDateTimeValue $command): void
    {
        $setting = $this->settingsRepository->findByItem($command->getItem());
        $value   = $command->getValue();
        if ($value === null) {
            $setting->setValue(null);
        } else {
            $setting->setValue($value->format(DateTimeImmutable::ISO8601));
        }

        $this->settingsRepository->save($setting);
    }
}
