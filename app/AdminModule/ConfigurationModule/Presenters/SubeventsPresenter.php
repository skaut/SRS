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
use Nette\DI\Attributes\Inject;
use stdClass;
use Throwable;

/**
 * Presenter obsluhující správu podakcí.
 */
class SubeventsPresenter extends ConfigurationBasePresenter
{
    #[Inject]
    public ISubeventsGridControlFactory $subeventsGridControlFactory;

    #[Inject]
    public SubeventFormFactory $subeventFormFactory;

    #[Inject]
    public SubeventsFormFactory $subeventsFormFactory;

    #[Inject]
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
            if ($form->isSubmitted() != $form['cancel']) {
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
