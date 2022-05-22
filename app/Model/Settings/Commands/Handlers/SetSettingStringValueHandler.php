<?php

declare(strict_types=1);

namespace App\Model\Settings\Commands\Handlers;

use App\Model\Settings\Commands\SetSettingStringValue;
use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Settings\Repositories\SettingsRepository;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class SetSettingStringValueHandler implements MessageHandlerInterface
{
    public function __construct(private SettingsRepository $settingsRepository)
    {
    }

    /**
     * @throws SettingsItemNotFoundException
     */
    public function __invoke(SetSettingStringValue $command): void
    {
        $setting = $this->settingsRepository->findByItem($command->getItem());
        $setting->setValue($command->getValue());
        $this->settingsRepository->save($setting);
    }
}
