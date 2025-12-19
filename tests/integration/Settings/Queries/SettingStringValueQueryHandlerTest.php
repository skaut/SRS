<?php

declare(strict_types=1);

namespace App\Model\Settings\Queries\Handlers;

use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Repositories\SettingsRepository;
use App\Model\Settings\Settings;
use CommandHandlerTest;
use Exception;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Throwable;

final class SettingStringValueQueryHandlerTest extends CommandHandlerTest
{
    private const ITEM = 'test_item';

    private SettingsRepository $settingsRepository;

    /**
     * Načtení hodnoty.
     */
    public function testGetValue(): void
    {
        $value = 'test';

        $this->settingsRepository->save(new Settings(self::ITEM, $value));

        $result = $this->queryBus->handle(new SettingStringValueQuery(self::ITEM));

        $this->assertEquals($value, $result);
    }

    /**
     * Načtení hodnoty null.
     */
    public function testGetValueNull(): void
    {
        $this->settingsRepository->save(new Settings(self::ITEM, null));

        $result = $this->queryBus->handle(new SettingStringValueQuery(self::ITEM));

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
            $this->queryBus->handle(new SettingStringValueQuery(self::ITEM));
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
        $tester = new IntegrationTester($this->getScenario());
        $tester->useConfigFiles([__DIR__ . '/SettingStringValueQueryHandlerTest.neon']);

        parent::_before();

        $this->settingsRepository = $tester->grabService(SettingsRepository::class);
    }
}
