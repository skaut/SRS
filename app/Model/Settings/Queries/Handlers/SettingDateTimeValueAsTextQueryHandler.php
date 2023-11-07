<?php

declare(strict_types=1);

namespace App\Model\Settings\Queries\Handlers;

use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Settings\Queries\SettingDateTimeValueAsTextQuery;
use App\Model\Settings\Repositories\SettingsRepository;
use App\Utils\Helpers;
use DateTimeImmutable;
use Exception;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class SettingDateTimeValueAsTextQueryHandler implements MessageHandlerInterface
{
    public function __construct(private readonly SettingsRepository $settingsRepository)
    {
    }

    /**
     * @throws SettingsItemNotFoundException
     * @throws Exception
     */
    public function __invoke(SettingDateTimeValueAsTextQuery $query): string|null
    {
        $setting = $this->settingsRepository->findByItem($query->getItem());
        $value   = $setting->getValue();
        if ($value === null) {
            return null;
        }

        return (new DateTimeImmutable($value))->format(Helpers::DATETIME_FORMAT);
    }
}
