<?php

declare(strict_types=1);

namespace App\Model\Settings\Commands\Handlers;

use App\Model\Program\Commands\RemoveBlock;
use App\Model\Program\Commands\RemoveProgram;
use App\Model\Program\Repositories\BlockRepository;
use App\Model\Settings\Commands\SetSettingBoolValue;
use App\Model\Settings\Commands\SetSettingIntValue;
use App\Model\Settings\Commands\SetSettingStringValue;
use App\Model\Settings\Exceptions\SettingsException;
use App\Model\Settings\Repositories\SettingsRepository;
use App\Services\CommandBus;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class SetSettingIntValueHandler implements MessageHandlerInterface
{
    private SettingsRepository $settingsRepository;

    public function __construct(SettingsRepository $settingsRepository)
    {
        $this->settingsRepository = $settingsRepository;
    }

    /**
     * @throws SettingsException
     */
    public function __invoke(SetSettingIntValue $command): void
    {
        $setting = $this->settingsRepository->findByItem($command->getItem());
        $value = $command->getValue();
        if ($value === null) {
            $setting->setValue(null);
        } else {
            $setting->setValue((string) $value);
        }
        $this->settingsRepository->save($setting);
    }
}
