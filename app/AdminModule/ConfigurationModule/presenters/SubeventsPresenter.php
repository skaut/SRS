<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Presenters;

use App\AdminModule\ConfigurationModule\Components\ISubeventsGridControlFactory;
use App\AdminModule\ConfigurationModule\Forms\SubeventForm;
use App\AdminModule\ConfigurationModule\Forms\SubeventsForm;
use App\Model\Settings\SettingsException;
use App\Model\Structure\SubeventRepository;
use Nette\Forms\Form;

/**
 * Presenter obsluhující správu podakcí.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SubeventsPresenter extends ConfigurationBasePresenter
{
    /**
     * @var ISubeventsGridControlFactory
     * @inject
     */
    public $subeventsGridControlFactory;

    /**
     * @var SubeventForm
     * @inject
     */
    public $subeventFormFactory;

    /**
     * @var SubeventsForm
     * @inject
     */
    public $subeventsFormFactory;

    /**
     * @var SubeventRepository
     * @inject
     */
    public $subeventRepository;


    public function renderEdit($id) : void
    {
        $subevent = $this->subeventRepository->findById($id);

        $this->template->editedSubevent = $subevent;
    }

    protected function createComponentSubeventsGrid()
    {
        return $this->subeventsGridControlFactory->create();
    }

    protected function createComponentSubeventForm()
    {
        $form = $this->subeventFormFactory->create($this->getParameter('id'));

        $form->onSuccess[] = function (Form $form, array $values) : void {
            if (! $form['cancel']->isSubmittedBy()) {
                $this->flashMessage('admin.configuration.subevents_saved', 'success');
            }

            $this->redirect('Subevents:default');
        };

        return $form;
    }

    /**
     * @throws SettingsException
     * @throws \Throwable
     */
    protected function createComponentSubeventsForm() : \Nette\Application\UI\Form
    {
        $form = $this->subeventsFormFactory->create();

        $form->onSuccess[] = function (Form $form, array $values) : void {
            $this->flashMessage('admin.configuration.configuration_saved', 'success');
            $this->redirect('this');
        };

        return $form;
    }
}
