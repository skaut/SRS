<?php

declare(strict_types=1);

namespace App\Model\Settings\Queries\Handlers;

use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Repositories\SettingsRepository;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class SettingStringValueQueryHandler implements MessageHandlerInterface
{
    public function __construct(private SettingsRepository $settingsRepository)
    {
    }

    /** @throws SettingsItemNotFoundException */
    public function __invoke(SettingStringValueQuery $query): string|null
    {
        $setting = $this->settingsRepository->findByItem($query->getItem());

        return $setting->getValue();
    }
}
