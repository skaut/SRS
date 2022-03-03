<?php

declare(strict_types=1);

namespace App\AdminModule\MailingModule\Components;

use App\Model\Mailing\Repositories\TemplateRepository;
use Doctrine\ORM\ORMException;
use Nette\Application\AbortException;
use Nette\Application\UI\Control;
use Nette\Localization\Translator;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Exception\DataGridColumnStatusException;
use Ublaboo\DataGrid\Exception\DataGridException;

/**
 * Komponenta pro správu automatických e-mailů.
 */
class MailTemplatesGridControl extends Control
{
    private Translator $translator;

    private TemplateRepository $templateRepository;

    public function __construct(Translator $translator, TemplateRepository $templateRepository)
    {
        $this->translator         = $translator;
        $this->templateRepository = $templateRepository;
    }

    /**
     * Vykreslí komponentu.
     */
    public function render(): void
    {
        $this->template->setFile(__DIR__ . '/templates/mail_templates_grid.latte');
        $this->template->render();
    }

    /**
     * Vytvoří komponentu.
     *
     * @throws DataGridColumnStatusException
     * @throws DataGridException
     */
    public function createComponentMailTemplatesGrid(string $name): DataGrid
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->templateRepository->createQueryBuilder('t')
            ->where('t.systemTemplate = FALSE'));
        $grid->setDefaultSort(['type' => 'ASC']);
        $grid->setPagination(false);

        $grid->addColumnText('type', 'admin.mailing.templates.type')
            ->setRenderer(function ($row) {
                return $this->translator->translate('common.mailing.template_type.' . $row->getType());
            });

        $grid->addColumnStatus('active', 'admin.mailing.templates.active')
            ->addOption(false, 'admin.mailing.templates.active_inactive')
            ->setClass('btn-danger')
            ->endOption()
            ->addOption(true, 'admin.mailing.templates.active_active')
            ->setClass('btn-success')
            ->endOption()
            ->onChange[] = [$this, 'changeActive'];

        $grid->addAction('edit', 'admin.common.edit', 'Templates:edit');

        return $grid;
    }

    /**
     * Aktivuje/deaktivuje automatický e-mail.
     *
     * @throws ORMException
     * @throws AbortException
     */
    public function changeActive(string $id, string $active): void
    {
        $p = $this->getPresenter();

        $template = $this->templateRepository->findById((int) $id);

        if ($template->isSystemTemplate() && ! $active) {
            $p->flashMessage('admin.mailing.templates.change_active_denied', 'danger');
        } else {
            $template->setActive((bool) $active);
            $this->templateRepository->save($template);

            $p->flashMessage('admin.mailing.templates.changed_active', 'success');
        }

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this->getComponent('mailTemplatesGrid')->redrawItem($id);
        } else {
            $p->redirect('this');
        }
    }
}
