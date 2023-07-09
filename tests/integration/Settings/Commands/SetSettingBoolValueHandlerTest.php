<?php

declare(strict_types=1);

namespace App\Model\Settings\Commands\Handlers;

use App\Model\Settings\Commands\SetSettingBoolValue;
use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Settings\Repositories\SettingsRepository;
use App\Model\Settings\Settings;
use CommandHandlerTest;
use Exception;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

final class SetSettingBoolValueHandlerTest extends CommandHandlerTest
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
        $value = true;

        $this->commandBus->handle(new SetSettingBoolValue(self::ITEM, $value));

        $setting = $this->settingsRepository->findByItem(self::ITEM);
        $this->assertNotNull($setting);
        $this->assertEquals((string) $value, $setting->getValue());
    }

    /**
     * Nastavení hodnoty na null.
     */
    public function testSetValueNull(): void
    {
        $this->commandBus->handle(new SetSettingBoolValue(self::ITEM, null));

        $setting = $this->settingsRepository->findByItem(self::ITEM);
        $this->assertNotNull($setting);
        $this->assertNull($setting->getValue());
    }

    /**
     * Nastavení hodnoty neexistující položce.
     *
     * @throws Exception
     */
    public function testSetValueNotExistingItem(): void
    {
        $this->expectException(SettingsItemNotFoundException::class);
        try {
            $this->commandBus->handle(new SetSettingBoolValue('test_item_invalid', true));
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
        $this->tester->useConfigFiles([__DIR__ . '/SetSettingBoolValueHandlerTest.neon']);

        parent::_before();

        $this->settingsRepository = $this->tester->grabService(SettingsRepository::class);

        $this->settingsRepository->save(new Settings(self::ITEM, null));
    }
}
