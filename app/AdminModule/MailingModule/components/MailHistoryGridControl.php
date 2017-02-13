<?php

namespace App\AdminModule\MailingModule\Components;


use App\Model\Mailing\MailRepository;
use App\Model\Program\ProgramRepository;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
use Ublaboo\DataGrid\DataGrid;

class MailHistoryGridControl extends Control
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var MailRepository
     */
    private $mailRepository;

    public function __construct(Translator $translator, MailRepository $mailRepository)
    {
        $this->translator = $translator;
        $this->mailRepository = $mailRepository;
    }

    public function render()
    {
        $this->template->render(__DIR__ . '/templates/mail_history_grid.latte');
    }

    public function createComponentMailHistoryGrid($name)
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->mailRepository->createQueryBuilder('m'));
        $grid->setDefaultSort(['datetime' => 'DESC']);
        $grid->setPagination(false);

        $grid->addColumnDateTime('datetime', 'admin.mailing.history_datetime')
            ->setFormat('j. n. Y H:i');

        $grid->addColumnText('subject', 'admin.mailing.history_subject');

        $grid->addColumnText('recipients', 'admin.mailing.history_recipients')
            ->setRenderer(function($row) {
                return "";
            });
    }
}