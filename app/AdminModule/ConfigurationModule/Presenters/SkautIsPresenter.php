<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Presenters;

use App\AdminModule\ConfigurationModule\Components\ISkautIsEventEducationGridControlFactory;
use App\AdminModule\ConfigurationModule\Components\SkautIsEventEducationGridControl;
use App\AdminModule\ConfigurationModule\Forms\SkautIsEventFormFactory;
use App\Model\Enums\SkautIsEventType;
use App\Model\Settings\Commands\SetSettingStringValue;
use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Settings;
use App\Model\SkautIs\Repositories\SkautIsCourseRepository;
use App\Services\CommandBus;
use Nette\Application\AbortException;
use Nette\Application\UI\Form;
use Nette\DI\Attributes\Inject;
use stdClass;
use Throwable;

/**
 * Presenter obsluhující nastavení propojení se skautIS akcí.
 */
class SkautIsPresenter extends ConfigurationBasePresenter
{
    #[Inject]
    public CommandBus $commandBus;

    #[Inject]
    public SkautIsEventFormFactory $skautIsEventFormFactory;

    #[Inject]
    public ISkautIsEventEducationGridControlFactory $skautISEventEducationGridControlFactory;

    #[Inject]
    public SkautIsCourseRepository $skautIsCourseRepository;

    /**
     * @throws Throwable
     */
    public function renderDefault(): void
    {
        $eventId = $this->queryBus->handle(new SettingStringValueQuery(Settings::SKAUTIS_EVENT_ID));
        if ($eventId !== null) {
            $this->template->event          = $this->queryBus->handle(new SettingStringValueQuery(Settings::SKAUTIS_EVENT_NAME));
            $this->template->connected      = true;
            $this->template->eventEducation = $this->queryBus->handle(new SettingStringValueQuery(Settings::SKAUTIS_EVENT_TYPE)) === SkautIsEventType::EDUCATION;
        } else {
            $this->template->connected = false;
        }
    }

    /**
     * Zruší propojení s akcí ve skautIS.
     *
     * @throws AbortException
     * @throws Throwable
     */
    public function handleDisconnect(): void
    {
        $this->commandBus->handle(new SetSettingStringValue(Settings::SKAUTIS_EVENT_ID, null));
        $this->commandBus->handle(new SetSettingStringValue(Settings::SKAUTIS_EVENT_NAME, null));

        if ($this->queryBus->handle(new SettingStringValueQuery(Settings::SKAUTIS_EVENT_TYPE)) === SkautIsEventType::EDUCATION) {
            $this->skautIsCourseRepository->removeAll();
        }

        $this->flashMessage('admin.configuration.skautis_event_disconnect_successful', 'success');
        $this->redirect('this');
    }

    /**
     * @throws SettingsItemNotFoundException
     * @throws Throwable
     */
    protected function createComponentSkautIsEventForm(): Form
    {
        $form = $this->skautIsEventFormFactory->create();

        $form->onSuccess[] = function (Form $form, stdClass $values): void {
            $this->flashMessage('admin.configuration.skautis_event_connect_successful', 'success');
            $this->redirect('this');
        };

        return $form;
    }

    protected function createComponentSkautIsEventEducationGrid(): SkautIsEventEducationGridControl
    {
        return $this->skautISEventEducationGridControlFactory->create();
    }
}
