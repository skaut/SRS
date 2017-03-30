<?php

namespace App\AdminModule\MailingModule\Components;

use App\Model\Mailing\Mail;
use App\Model\Mailing\MailRepository;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
use Ublaboo\DataGrid\DataGrid;


/**
 * Komponenta pro výpis historie e-mailů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class MailHistoryGridControl extends Control
{
    /** @var Translator */
    private $translator;

    /** @var MailRepository */
    private $mailRepository;


    /**
     * MailHistoryGridControl constructor.
     * @param Translator $translator
     * @param MailRepository $mailRepository
     */
    public function __construct(Translator $translator, MailRepository $mailRepository)
    {
        parent::__construct();

        $this->translator = $translator;
        $this->mailRepository = $mailRepository;
    }

    /**
     * Vykreslí komponentu.
     */
    public function render()
    {
        $this->template->render(__DIR__ . '/templates/mail_history_grid.latte');
    }

    /**
     * Vytvoří komponentu.
     * @param $name
     */
    public function createComponentMailHistoryGrid($name)
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->mailRepository->createQueryBuilder('m'));
        $grid->setDefaultSort(['datetime' => 'DESC']);
        $grid->setPagination(FALSE);

        $grid->addColumnText('recipients', 'admin.mailing.history_recipients')
            ->setRenderer(function ($row) {
                if ($row->getType() == Mail::TO_ROLES) {
                    $roles = [];
                    foreach ($row->getRecipientRoles() as $role) {
                        $roles[] = $role->getName();
                    }
                    $rolesText = implode(", ", $roles);
                    return $this->translator->translate('admin.mailing.history_roles', NULL, ['roles' => $rolesText]);
                } else {
                    return $this->translator->translate('admin.mailing.history_user', NULL, ['name' => $row->getRecipientUser()->getDisplayName()]);
                }
            });

        $grid->addColumnText('subject', 'admin.mailing.history_subject');

        $grid->addColumnDateTime('datetime', 'admin.mailing.history_datetime')
            ->setFormat('j. n. Y H:i');
    }
}
