<?php

namespace App\AdminModule\ProgramModule\Components;


use App\Model\Program\BlockRepository;
use App\Model\Program\ProgramRepository;
use App\Model\Settings\SettingsRepository;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
use Ublaboo\DataGrid\DataGrid;

class ProgramBlockScheduleGridControl extends Control
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var ProgramRepository
     */
    private $programRepository;

    /**
     * @var SettingsRepository
     */
    private $settingsRepository;

    public function __construct(Translator $translator, ProgramRepository $programRepository, SettingsRepository $settingsRepository)
    {
        $this->translator = $translator;
        $this->programRepository = $programRepository;
        $this->settingsRepository = $settingsRepository;
    }

    public function render()
    {
        $this->template->render(__DIR__ . '/templates/program_block_schedule_grid.latte');
    }

    public function createComponentProgramBlockScheduleGrid($name)
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->programRepository->createQueryBuilder('p')
            ->addSelect('b')->innerJoin('p.block', 'b')
            ->where('b.id = :id')
            ->setParameter('id', $this->getPresenter()->getParameter('id'))
        );
        $grid->setDefaultSort(['start' => 'ASC']);
        $grid->setPagination(false);

        $grid->addColumnDateTime('start', 'admin.program.blocks_program_start')
            ->setFormat('j. n. Y H:i');

        $basicBlockDuration = $this->settingsRepository->getValue('basic_block_duration');

        $grid->addColumnDateTime('end', 'admin.program.blocks_program_end')
            ->setRenderer(function ($row) use ($basicBlockDuration) {
                return $row->getEnd($basicBlockDuration)->format('j. n. Y H:i');
            });

        $grid->addColumnText('room', 'admin.program.blocks_program_room', 'room.name');

        $grid->addColumnText('occupancy', 'admin.program.blocks_program_occupancy');

        //TODO seznamy přihlášených a nepřihlášených uživatelů - 2 datagirdy
    }
}