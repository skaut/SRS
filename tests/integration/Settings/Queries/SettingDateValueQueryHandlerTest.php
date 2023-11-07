<?php

declare(strict_types=1);

namespace App\Model\Settings\Queries\Handlers;

use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Settings\Queries\SettingDateValueQuery;
use App\Model\Settings\Repositories\SettingsRepository;
use App\Model\Settings\Settings;
use CommandHandlerTest;
use DateTimeImmutable;
use Exception;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Throwable;

final class SettingDateValueQueryHandlerTest extends CommandHandlerTest
{
    private const ITEM = 'test_item';

    private SettingsRepository $settingsRepository;

    /**
     * Načtení hodnoty.
     */
    public function testGetValue(): void
    {
        $value = new DateTimeImmutable();

        $this->settingsRepository->save(new Settings(self::ITEM, $value->format('Y-m-d')));

        $result = $this->queryBus->handle(new SettingDateValueQuery(self::ITEM));

        $expected = $value->setTime(0, 0);

        $this->assertEquals($expected, $result);
    }

    /**
     * Načtení hodnoty null.
     */
    public function testGetValueNull(): void
    {
        $this->settingsRepository->save(new Settings(self::ITEM, null));

        $result = $this->queryBus->handle(new SettingDateValueQuery(self::ITEM));

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
            $this->queryBus->handle(new SettingDateValueQuery(self::ITEM));
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
        $this->tester->useConfigFiles([__DIR__ . '/SettingDateValueQueryHandlerTest.neon']);

        parent::_before();

        $this->settingsRepository = $this->tester->grabService(SettingsRepository::class);
    }
}
