<?php

declare(strict_types=1);

namespace App\Model\Settings\Queries\Handlers;

use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Settings\Queries\SettingDateTimeValueAsTextQuery;
use App\Model\Settings\Repositories\SettingsRepository;
use App\Model\Settings\Settings;
use App\Utils\Helpers;
use CommandHandlerTest;
use DateTimeImmutable;
use Exception;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Throwable;

use const DATE_ATOM;

final class SettingDateTimeValueAsTextQueryHandlerTest extends CommandHandlerTest
{
    private const ITEM = 'test_item';

    private SettingsRepository $settingsRepository;

    /**
     * Načtení hodnoty.
     */
    public function testGetValue(): void
    {
        $value = new DateTimeImmutable();

        $this->settingsRepository->save(new Settings(self::ITEM, $value->format(DATE_ATOM)));

        $result = $this->queryBus->handle(new SettingDateTimeValueAsTextQuery(self::ITEM));

        $this->assertEquals($value->format(Helpers::DATETIME_FORMAT), $result);
    }

    /**
     * Načtení hodnoty null.
     */
    public function testGetValueNull(): void
    {
        $this->settingsRepository->save(new Settings(self::ITEM, null));

        $result = $this->queryBus->handle(new SettingDateTimeValueAsTextQuery(self::ITEM));

        $this->assertNull($result);
    }

    /**
     * Načtení hodnoty neexistující položky.
     *
     * @throws Exception
     * @throws Throwable
     */
    public function testGetValueNotExistingItem(): void
    {
        $this->expectException(SettingsItemNotFoundException::class);
        try {
            $this->queryBus->handle(new SettingDateTimeValueAsTextQuery(self::ITEM));
        } catch (HandlerFailedException $e) {
            throw $e->getPrevious();
        }
    }

    /** @return string[] */
    protected function getTestedAggregateRoots(): array
    {
        return [Settings::class];
    }

    protected function _before(): void
    {
        $tester->useConfigFiles([__DIR__ . '/SettingDateTimeValueAsTextQueryHandlerTest.neon']);

        parent::_before();

        $this->settingsRepository = $tester->grabService(SettingsRepository::class);
    }
}
