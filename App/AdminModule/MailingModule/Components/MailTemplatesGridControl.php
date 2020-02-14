<?php

declare(strict_types=1);

namespace App\AdminModule\MailingModule\Components;

use App\Model\Mailing\TemplateRepository;
use Doctrine\ORM\ORMException;
use Nette\Application\AbortException;
use Nette\Application\UI\Control;
use Nette\Localization\ITranslator;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Exception\DataGridColumnStatusException;
use Ublaboo\DataGrid\Exception\DataGridException;

/**
 * Komponenta pro správu automatických e-mailů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class MailTemplatesGridControl extends Control
{
    /** @var ITranslator */
    private $translator;

    /** @var TemplateRepository */
    private $templateRepository;

    public function __construct(ITranslator $translator, TemplateRepository $templateRepository)
    {
        $this->translator         = $translator;
        $this->templateRepository = $templateRepository;
    }

    /**
     * Vykreslí komponentu.
     */
    public function render() : void
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
    public function createComponentMailTemplatesGrid(string $name) : void
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->templateRepository->createQueryBuilder('t')
            ->where('t.system = FALSE'));
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

        $grid->addColumnText('sendToUser', 'admin.mailing.templates.send_to_user')
            ->setReplacement([
                '0' => $this->translator->translate('admin.common.no'),
                '1' => $this->translator->translate('admin.common.yes'),
            ]);

        $grid->addColumnText('sendToOrganizer', 'admin.mailing.templates.send_to_organizer')
            ->setReplacement([
                '0' => $this->translator->translate('admin.common.no'),
                '1' => $this->translator->translate('admin.common.yes'),
            ]);

        $grid->addAction('edit', 'admin.common.edit', 'Templates:edit');
    }

    /**
     * Aktivuje/deaktivuje automatický e-mail.
     *
     * @throws ORMException
     * @throws AbortException
     */
    public function changeActive(string $id, string $active) : void
    {
        $p = $this->getPresenter();

        $template = $this->templateRepository->findById((int) $id);

        if ($template->isSystem() && ! $active) {
            $p->flashMessage('admin.mailing.templates.change_active_denied', 'danger');
        } else {
            $template->setActive((bool) $active);
            $this->templateRepository->save($template);

            $p->flashMessage('admin.mailing.templates.changed_active', 'success');
        }

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            /** @var DataGrid $mailTemplatesGrid */
            $mailTemplatesGrid = $this['mailTemplatesGrid'];
            $mailTemplatesGrid->redrawItem($id);
        } else {
            $this->redirect('this');
        }
    }
}
