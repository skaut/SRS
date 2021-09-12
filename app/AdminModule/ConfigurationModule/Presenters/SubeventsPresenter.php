<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Presenters;

use App\AdminModule\ConfigurationModule\Components\ISubeventsGridControlFactory;
use App\AdminModule\ConfigurationModule\Components\SubeventsGridControl;
use App\AdminModule\ConfigurationModule\Forms\SubeventFormFactory;
use App\AdminModule\ConfigurationModule\Forms\SubeventsFormFactory;
use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Structure\Repositories\SubeventRepository;
use Nette\Application\UI\Form;
use stdClass;
use Throwable;

/**
 * Presenter obsluhující správu podakcí.
 */
class SubeventsPresenter extends ConfigurationBasePresenter
{
    /** @inject */
    public ISubeventsGridControlFactory $subeventsGridControlFactory;

    /** @inject */
    public SubeventFormFactory $subeventFormFactory;

    /** @inject */
    public SubeventsFormFactory $subeventsFormFactory;

    /** @inject */
    public SubeventRepository $subeventRepository;

    public function renderEdit(int $id): void
    {
        $subevent = $this->subeventRepository->findById($id);

        $this->template->editedSubevent = $subevent;
    }

    protected function createComponentSubeventsGrid(): SubeventsGridControl
    {
        return $this->subeventsGridControlFactory->create();
    }

    protected function createComponentSubeventForm(): Form
    {
        $form = $this->subeventFormFactory->create((int) $this->getParameter('id'));

        $form->onSuccess[] = function (Form $form, stdClass $values): void {
            if ($form->isSubmitted() !== $form['cancel']) {
                $this->flashMessage('admin.configuration.subevents_saved', 'success');
            }

            $this->redirect('Subevents:default');
        };

        return $form;
    }

    /**
     * @throws SettingsItemNotFoundException
     * @throws Throwable
     */
    protected function createComponentSubeventsForm(): Form
    {
        $form = $this->subeventsFormFactory->create();

        $form->onSuccess[] = function (Form $form, stdClass $values): void {
            $this->flashMessage('admin.configuration.configuration_saved', 'success');
            $this->redirect('this');
        };

        return $form;
    }
}
