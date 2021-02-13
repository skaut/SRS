<?php

declare(strict_types=1);

namespace App\Model\Settings\Queries\Handlers;

use App\Model\Settings\Exceptions\SettingsException;
use App\Model\Settings\Queries\SettingArrayValueQuery;
use App\Model\Settings\Repositories\SettingsRepository;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

use function unserialize;

class SettingArrayValueQueryHandler implements MessageHandlerInterface
{
    private SettingsRepository $settingsRepository;

    public function __construct(SettingsRepository $settingsRepository)
    {
        $this->settingsRepository = $settingsRepository;
    }

    /**
     * @return mixed[]
     *
     * @throws SettingsException
     */
    public function __invoke(SettingArrayValueQuery $query): array
    {
        $setting = $this->settingsRepository->findByItem($query->getItem());

        return unserialize($setting->getValue());
    }
}
