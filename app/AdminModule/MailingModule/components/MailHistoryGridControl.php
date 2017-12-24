<?php

namespace App\AdminModule\MailingModule\Components;

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
        $grid->setPagination(TRUE);

        $grid->addColumnText('recipientRoles', 'admin.mailing.history_recipient_roles', 'recipientRolesText');

        $grid->addColumnText('recipientUsers', 'admin.mailing.history_recipient_users', 'recipientUsersText');

        $grid->addColumnText('subject', 'admin.mailing.history_subject');

        $grid->addColumnDateTime('datetime', 'admin.mailing.history_datetime')
            ->setFormat('j. n. Y H:i');

        $grid->addColumnText('automatic', 'admin.mailing.history_automatic')
            ->setReplacement([
                '0' => $this->translator->translate('admin.common.no'),
                '1' => $this->translator->translate('admin.common.yes')
            ])
            ->setFilterSelect([
                '' => 'admin.common.all',
                '0' => 'admin.common.no',
                '1' => 'admin.common.yes'
            ])
            ->setTranslateOptions();
    }
}
