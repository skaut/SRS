<?php

declare(strict_types=1);

namespace App\Model\Settings\Queries\Handlers;

use App\Model\Settings\Exceptions\SettingsException;
use App\Model\Settings\Queries\SettingDateValueAsTextQuery;
use App\Model\Settings\Repositories\SettingsRepository;
use App\Utils\Helpers;
use DateTimeImmutable;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class SettingDateValueAsTextQueryHandler implements MessageHandlerInterface
{
    private SettingsRepository $settingsRepository;

    public function __construct(SettingsRepository $settingsRepository)
    {
        $this->settingsRepository = $settingsRepository;
    }

    /**
     * @throws SettingsException
     */
    public function __invoke(SettingDateValueAsTextQuery $query): ?string
    {
        $setting = $this->settingsRepository->findByItem($query->getItem());
        $value = $setting->getValue();
        if ($value === null) {
            return null;
        }

        return (new DateTimeImmutable($value))->format(Helpers::DATE_FORMAT);
    }
}
