<?php

declare(strict_types=1);

namespace App\Model\Settings\Commands\Handlers;

use App\Model\Settings\Commands\SetSettingDateValue;
use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Settings\Repositories\SettingsRepository;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class SetSettingDateValueHandler implements MessageHandlerInterface
{
    private SettingsRepository $settingsRepository;

    public function __construct(SettingsRepository $settingsRepository)
    {
        $this->settingsRepository = $settingsRepository;
    }

    /**
     * @throws SettingsItemNotFoundException
     */
    public function __invoke(SetSettingDateValue $command): void
    {
        $setting = $this->settingsRepository->findByItem($command->getItem());
        $value   = $command->getValue();
        if ($value === null) {
            $setting->setValue(null);
        } else {
            $setting->setValue($value->format('Y-m-d'));
        }

        $this->settingsRepository->save($setting);
    }
}
