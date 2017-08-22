<?php

namespace App\AdminModule\MailingModule\Components;

use App\Model\Mailing\Mail;
use App\Model\Mailing\MailRepository;
use App\Model\Mailing\TemplateRepository;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
use Ublaboo\DataGrid\DataGrid;


/**
 * Komponenta pro správu automatických e-mailů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class MailTemplatesGridControl extends Control
{
    /** @var Translator */
    private $translator;

    /** @var TemplateRepository */
    private $templateRepository;


    /**
     * MailTemplatesGridControl constructor.
     * @param Translator $translator
     * @param TemplateRepository $templateRepository
     */
    public function __construct(Translator $translator, TemplateRepository $templateRepository)
    {
        parent::__construct();

        $this->translator = $translator;
        $this->templateRepository = $templateRepository;
    }

    /**
     * Vykreslí komponentu.
     */
    public function render()
    {
        $this->template->render(__DIR__ . '/templates/mail_templates_grid.latte');
    }

    /**
     * Vytvoří komponentu.
     * @param $name
     */
    public function createComponentMailTemplatesGrid($name)
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->templateRepository->createQueryBuilder('t'));
        $grid->setPagination(FALSE);

        $grid->addColumnText('type', 'admin.mailing.templates_type')
            ->setRenderer(function ($row) {
                return $this->translator->translate('common.mailing.template_type.' . $row->getType());
            });

        $grid->addColumnStatus('active', 'admin.mailing.templates_active')
            ->addOption(FALSE, 'admin.mailing.templates_active_inactive')
            ->setClass('btn-danger')
            ->endOption()
            ->addOption(TRUE, 'admin.mailing.templates_active_active')
            ->setClass('btn-success')
            ->endOption()
            ->onChange[] = [$this, 'changeActive'];

        $grid->addAction('edit', 'admin.common.edit', 'Templates:edit');
    }

    /**
     * Aktivuje/deaktivuje automatický e-mail.
     * @param $id
     * @param $active
     */
    public function changeActive($id, $active)
    {
        $template = $this->templateRepository->findById($id);

        $template->setActive($active);
        $this->templateRepository->save($template);

        $p = $this->getPresenter();
        $p->flashMessage('admin.mailing.templates_changed_active', 'success');

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this['mailTemplatesGrid']->redrawItem($id);
        } else {
            $this->redirect('this');
        }
    }
}
