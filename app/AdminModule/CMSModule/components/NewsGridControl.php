<?php
declare(strict_types=1);

namespace App\AdminModule\CMSModule\Components;

use App\Model\CMS\NewsRepository;
use App\Utils\Helpers;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
use Ublaboo\DataGrid\DataGrid;


/**
 * Komponenta pro správu aktualit.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class NewsGridControl extends Control
{
    /** @var Translator */
    private $translator;

    /** @var NewsRepository */
    private $newsRepository;


    /**
     * NewsGridControl constructor.
     * @param Translator $translator
     * @param NewsRepository $newsRepository
     */
    public function __construct(Translator $translator, NewsRepository $newsRepository)
    {
        parent::__construct();

        $this->translator = $translator;
        $this->newsRepository = $newsRepository;
    }

    /**
     * Vykreslí komponentu.
     */
    public function render()
    {
        $this->template->render(__DIR__ . '/templates/news_grid.latte');
    }

    /**
     * Vytvoří komponentu.
     * @param $name
     * @throws \Ublaboo\DataGrid\Exception\DataGridColumnStatusException
     * @throws \Ublaboo\DataGrid\Exception\DataGridException
     */
    public function createComponentNewsGrid($name)
    {
        $grid = new DataGrid($this, $name);
        $grid->setTemplateFile(__DIR__ . '/templates/news_grid_template.latte');
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->newsRepository->createQueryBuilder('n'));
        $grid->setDefaultSort(['published' => 'DESC']);
        $grid->setPagination(FALSE);

        $grid->addColumnDateTime('published', 'admin.cms.news_published')
            ->setFormat(Helpers::DATETIME_FORMAT);

        $columnMandatory = $grid->addColumnStatus('pinned', 'admin.cms.news_pinned');
        $columnMandatory
            ->addOption(FALSE, 'admin.cms.news_pinned_unpinned')
            ->setClass('btn-primary')
            ->endOption()
            ->addOption(TRUE, 'admin.cms.news_pinned_pinned')
            ->setClass('btn-warning')
            ->endOption()
            ->onChange[] = [$this, 'changePinned'];

        $grid->addColumnText('text', 'admin.cms.news_text');


        $grid->addToolbarButton('News:add')
            ->setIcon('plus')
            ->setTitle('admin.common.add');

        $grid->addAction('edit', 'admin.common.edit', 'News:edit');

        $grid->addAction('delete', '', 'delete!')
            ->setIcon('trash')
            ->setTitle('admin.common.delete')
            ->setClass('btn btn-xs btn-danger')
            ->addAttributes([
                'data-toggle' => 'confirmation',
                'data-content' => $this->translator->translate('admin.cms.news_delete_confirm')
            ]);
    }

    /**
     * Zpracuje odstranění aktuality.
     * @param $id
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Nette\Application\AbortException
     */
    public function handleDelete($id)
    {
        $news = $this->newsRepository->findById($id);
        $this->newsRepository->remove($news);

        $this->getPresenter()->flashMessage('admin.cms.news_deleted', 'success');

        $this->redirect('this');
    }

    /**
     * Změní připíchnutí aktuality.
     * @param $id
     * @param $pinned
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Nette\Application\AbortException
     */
    public function changePinned($id, $pinned)
    {
        $news = $this->newsRepository->findById($id);
        $news->setPinned($pinned);
        $this->newsRepository->save($news);

        $p = $this->getPresenter();
        $p->flashMessage('admin.cms.news_changed_pinned', 'success');

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this['newsGrid']->redrawItem($id);
        } else {
            $this->redirect('this');
        }
    }
}
