<?php

declare(strict_types=1);

namespace App\Model\Settings\Commands\Handlers;

use App\Model\Settings\Commands\SetSettingArrayValue;
use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Settings\Repositories\SettingsRepository;
use App\Model\Settings\Settings;
use CommandHandlerTest;
use Exception;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Throwable;

use function serialize;

final class SetSettingArrayValueHandlerTest extends CommandHandlerTest
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
        $value = ['item1', 'item2', 'item3'];

        $this->commandBus->handle(new SetSettingArrayValue(self::ITEM, $value));

        $setting = $this->settingsRepository->findByItem(self::ITEM);
        $this->assertNotNull($setting);
        $this->assertEquals(serialize($value), $setting->getValue());
    }

    /**
     * Nastavení hodnoty na null.
     *
     * @throws SettingsItemNotFoundException
     */
    public function testSetValueNull(): void
    {
        $this->commandBus->handle(new SetSettingArrayValue(self::ITEM, null));

        $setting = $this->settingsRepository->findByItem(self::ITEM);
        $this->assertNotNull($setting);
        $this->assertEquals(serialize(null), $setting->getValue());
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
            $this->commandBus->handle(new SetSettingArrayValue('test_item_invalid', []));
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
        $this->getModule('IntegrationTester')->useConfigFiles([__DIR__ . '/SetSettingArrayValueHandlerTest.neon']);

        parent::_before();

        $this->settingsRepository = $this->getModule('IntegrationTester')->grabService(SettingsRepository::class);

        $this->settingsRepository->save(new Settings(self::ITEM, null));
    }
}
