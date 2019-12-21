<?php

declare(strict_types=1);

namespace App\AdminModule\CmsModule\Components;

use App\Model\Cms\NewsRepository;
use App\Utils\Helpers;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Nette\Application\AbortException;
use Nette\Application\UI\Control;
use Nette\Localization\ITranslator;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Exception\DataGridColumnStatusException;
use Ublaboo\DataGrid\Exception\DataGridException;

/**
 * Komponenta pro správu aktualit.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class NewsGridControl extends Control
{
    /** @var ITranslator */
    private $translator;

    /** @var NewsRepository */
    private $newsRepository;

    public function __construct(ITranslator $translator, NewsRepository $newsRepository)
    {
        parent::__construct();

        $this->translator     = $translator;
        $this->newsRepository = $newsRepository;
    }

    /**
     * Vykreslí komponentu.
     */
    public function render() : void
    {
        $this->template->setFile(__DIR__ . '/templates/news_grid.latte');
        $this->template->render();
    }

    /**
     * Vytvoří komponentu.
     *
     * @throws DataGridColumnStatusException
     * @throws DataGridException
     */
    public function createComponentNewsGrid(string $name) : void
    {
        $grid = new DataGrid($this, $name);
        $grid->setTemplateFile(__DIR__ . '/templates/news_grid_template.latte');
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->newsRepository->createQueryBuilder('n'));
        $grid->setDefaultSort(['published' => 'DESC']);
        $grid->setPagination(false);

        $grid->addColumnDateTime('published', 'admin.cms.news_published')
            ->setFormat(Helpers::DATETIME_FORMAT);

        $columnMandatory = $grid->addColumnStatus('pinned', 'admin.cms.news_pinned');
        $columnMandatory
            ->addOption(false, 'admin.cms.news_pinned_unpinned')
            ->setClass('btn-primary')
            ->endOption()
            ->addOption(true, 'admin.cms.news_pinned_pinned')
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
                'data-content' => $this->translator->translate('admin.cms.news_delete_confirm'),
            ]);
    }

    /**
     * Zpracuje odstranění aktuality.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws AbortException
     */
    public function handleDelete(int $id) : void
    {
        $news = $this->newsRepository->findById($id);
        $this->newsRepository->remove($news);

        $this->getPresenter()->flashMessage('admin.cms.news_deleted', 'success');

        $this->redirect('this');
    }

    /**
     * Změní připíchnutí aktuality.
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws AbortException
     */
    public function changePinned(int $id, bool $pinned) : void
    {
        $news = $this->newsRepository->findById($id);
        $news->setPinned($pinned);
        $this->newsRepository->save($news);

        $p = $this->getPresenter();
        $p->flashMessage('admin.cms.news_changed_pinned', 'success');

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            /** @var DataGrid $newsGrid */
            $newsGrid = $this['newsGrid'];
            $newsGrid->redrawItem($id);
        } else {
            $this->redirect('this');
        }
    }
}
