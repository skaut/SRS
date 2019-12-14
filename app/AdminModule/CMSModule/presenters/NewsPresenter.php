<?php

declare(strict_types=1);

namespace App\AdminModule\CMSModule\Presenters;

use App\AdminModule\CMSModule\Components\INewsGridControlFactory;
use App\AdminModule\CMSModule\Components\NewsGridControl;
use App\AdminModule\CMSModule\Forms\NewsForm;
use App\Model\CMS\NewsRepository;
use Nette\Application\UI\Form;
use stdClass;

/**
 * Presenter starající se o správu aktualit.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class NewsPresenter extends CMSBasePresenter
{
    /**
     * @var INewsGridControlFactory
     * @inject
     */
    public $newsGridControlFactory;

    /**
     * @var NewsForm
     * @inject
     */
    public $newsFormFactory;

    /**
     * @var NewsRepository
     * @inject
     */
    public $newsRepository;


    public function renderEdit(int $id) : void
    {
    }

    protected function createComponentNewsGrid() : NewsGridControl
    {
        return $this->newsGridControlFactory->create();
    }

    protected function createComponentNewsForm() : Form
    {
        $form = $this->newsFormFactory->create((int) $this->getParameter('id'));

        $form->onSuccess[] = function (Form $form, stdClass $values) : void {
            if ($form->isSubmitted() === $form['cancel']) {
                $this->redirect('News:default');
            }

            $this->flashMessage('admin.cms.news_saved', 'success');

            if ($form->isSubmitted() === $form['submitAndContinue']) {
                $id = $values->id ?: $this->newsRepository->findLastId();
                $this->redirect('News:edit', ['id' => $id]);
            } else {
                $this->redirect('News:default');
            }
        };

        return $form;
    }
}
