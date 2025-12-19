<?php

declare(strict_types=1);

namespace App\Model\Settings\Commands\Handlers;

use App\Model\Settings\Commands\SetSettingIntValue;
use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Settings\Repositories\SettingsRepository;
use App\Model\Settings\Settings;
use CommandHandlerTest;
use Exception;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Throwable;

final class SetSettingIntValueHandlerTest extends CommandHandlerTest
{
    private const ITEM = 'test_item';

    private SettingsRepository $settingsRepository;

    /**
     * Nastavení hodnoty.
     *
     * @throws SettingsItemNotFoundException
     */
    public function testSetValue(): void
    {
        $value = 10;

        $this->commandBus->handle(new SetSettingIntValue(self::ITEM, $value));

        $setting = $this->settingsRepository->findByItem(self::ITEM);
        $this->assertNotNull($setting);
        $this->assertEquals((string) $value, $setting->getValue());
    }

    /**
     * Nastavení hodnoty na null.
     *
     * @throws SettingsItemNotFoundException
     */
    public function testSetValueNull(): void
    {
        $this->commandBus->handle(new SetSettingIntValue(self::ITEM, null));

        $setting = $this->settingsRepository->findByItem(self::ITEM);
        $this->assertNotNull($setting);
        $this->assertNull($setting->getValue());
    }

    /**
     * Nastavení hodnoty neexistující položce.
     *
     * @throws Exception
     * @throws Throwable
     */
    public function testSetValueNotExistingItem(): void
    {
        $this->expectException(SettingsItemNotFoundException::class);
        try {
            $this->commandBus->handle(new SetSettingIntValue('test_item_invalid', 10));
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
        $tester->useConfigFiles([__DIR__ . '/SetSettingIntValueHandlerTest.neon']);

        parent::_before();

        $this->settingsRepository = $tester->grabService(SettingsRepository::class);

        $this->settingsRepository->save(new Settings(self::ITEM, null));
    }
}
