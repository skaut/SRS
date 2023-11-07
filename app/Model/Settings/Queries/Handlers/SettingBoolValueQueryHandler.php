<?php

declare(strict_types=1);

namespace App\Model\Settings\Queries\Handlers;

use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Settings\Queries\SettingBoolValueQuery;
use App\Model\Settings\Repositories\SettingsRepository;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

use function filter_var;

use const FILTER_VALIDATE_BOOLEAN;

class SettingBoolValueQueryHandler implements MessageHandlerInterface
{
    public function __construct(private readonly SettingsRepository $settingsRepository)
    {
    }

    /** @throws SettingsItemNotFoundException */
    public function __invoke(SettingBoolValueQuery $query): bool|null
    {
        $setting = $this->settingsRepository->findByItem($query->getItem());
        $value   = $setting->getValue();
        if ($value === null) {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
