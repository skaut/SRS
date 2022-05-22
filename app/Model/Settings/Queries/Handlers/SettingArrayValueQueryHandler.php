<?php

declare(strict_types=1);

namespace App\Model\Settings\Queries\Handlers;

use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Settings\Queries\SettingArrayValueQuery;
use App\Model\Settings\Repositories\SettingsRepository;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

use function unserialize;

class SettingArrayValueQueryHandler implements MessageHandlerInterface
{
    public function __construct(private SettingsRepository $settingsRepository)
    {
    }

    /**
     * @return mixed[]|null
     *
     * @throws SettingsItemNotFoundException
     */
    public function __invoke(SettingArrayValueQuery $query): ?array
    {
        $setting = $this->settingsRepository->findByItem($query->getItem());

        return unserialize($setting->getValue());
    }
}
