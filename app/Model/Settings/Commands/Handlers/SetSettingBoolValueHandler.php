<?php

declare(strict_types=1);

namespace App\Model\Settings\Commands\Handlers;

use App\Model\Settings\Commands\SetSettingBoolValue;
use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Settings\Repositories\SettingsRepository;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class SetSettingBoolValueHandler implements MessageHandlerInterface
{
    public function __construct(private SettingsRepository $settingsRepository)
    {
    }

    /** @throws SettingsItemNotFoundException */
    public function __invoke(SetSettingBoolValue $command): void
    {
        $setting = $this->settingsRepository->findByItem($command->getItem());
        $value   = $command->getValue();
        if ($value === null) {
            $setting->setValue(null);
        } else {
            $setting->setValue((string) $value);
        }

        $this->settingsRepository->save($setting);
    }
}
