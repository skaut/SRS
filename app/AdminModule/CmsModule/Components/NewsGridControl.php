<?php

declare(strict_types=1);

namespace App\AdminModule\CmsModule\Components;

use App\Model\Cms\Repositories\NewsRepository;
use App\Utils\Helpers;
use Doctrine\ORM\ORMException;
use Nette\Application\AbortException;
use Nette\Application\UI\Control;
use Nette\Localization\Translator;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Exception\DataGridColumnStatusException;
use Ublaboo\DataGrid\Exception\DataGridException;

/**
 * Komponenta pro správu aktualit.
 */
class NewsGridControl extends Control
{
    private Translator $translator;

    private NewsRepository $newsRepository;

    public function __construct(Translator $translator, NewsRepository $newsRepository)
    {
        $this->translator     = $translator;
        $this->newsRepository = $newsRepository;
    }

    /**
     * Vykreslí komponentu.
     */
    public function render(): void
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
    public function createComponentNewsGrid(string $name): DataGrid
    {
        $grid = new DataGrid($this, $name);
        $grid->setTemplateFile(__DIR__ . '/templates/news_grid_template.latte');
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->newsRepository->createQueryBuilder('n'));
        $grid->setDefaultSort(['published' => 'DESC']);
        $grid->setPagination(false);

        $grid->addColumnDateTime('published', 'admin.cms.news.common.published')
            ->setFormat(Helpers::DATETIME_FORMAT);

        $columnMandatory = $grid->addColumnStatus('pinned', 'admin.cms.news.column.pinned');
        $columnMandatory
            ->addOption(false, 'admin.cms.news.column.pinned_unpinned')
            ->setClass('btn-primary')
            ->endOption()
            ->addOption(true, 'admin.cms.news.column.pinned_pinned')
            ->setClass('btn-warning')
            ->endOption()
            ->onChange[] = [$this, 'changePinned'];

        $grid->addColumnText('text', 'admin.cms.news.common.text');

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
                'data-content' => $this->translator->translate('admin.cms.news.action.delete_confirm'),
            ]);

        return $grid;
    }

    /**
     * Zpracuje odstranění aktuality.
     *
     * @throws ORMException
     * @throws AbortException
     */
    public function handleDelete(int $id): void
    {
        $news = $this->newsRepository->findById($id);
        $this->newsRepository->remove($news);

        $this->getPresenter()->flashMessage('admin.cms.news.message.delete_success', 'success');
        $this->redirect('this');
    }

    /**
     * Změní připíchnutí aktuality.
     *
     * @throws ORMException
     * @throws AbortException
     */
    public function changePinned(string $id, string $pinned): void
    {
        $news = $this->newsRepository->findById((int) $id);
        $news->setPinned((bool) $pinned);
        $this->newsRepository->save($news);

        $p = $this->getPresenter();
        $p->flashMessage('admin.cms.news.message.pinned_change_success', 'success');

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $newsGrid = $this->getComponent('newsGrid');
            $newsGrid->redrawItem($id);
        } else {
            $this->redirect('this');
        }
    }
}
