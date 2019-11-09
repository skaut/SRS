<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Components;

use App\Model\SkautIs\SkautIsCourseRepository;
use App\Model\Structure\Subevent;
use App\Model\Structure\SubeventRepository;
use Kdyby\Translation\Translator;
use Nette\Application\AbortException;
use Nette\Application\UI\Control;
use Nette\Forms\Container;
use Ublaboo\DataGrid\DataGrid;

/**
 * Komponenta pro nastavení propojení se vzdělávací akcí.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SkautIsEventEducationGridControl extends Control
{
    /** @var Translator */
    private $translator;

    /** @var SubeventRepository */
    private $subeventRepository;

    /** @var SkautIsCourseRepository */
    private $skautIsCourseRepository;

    public function __construct(
        Translator $translator,
        SubeventRepository $subeventRepository,
        SkautIsCourseRepository $skautIsCourseRepository
    ) {
        parent::__construct();

        $this->translator              = $translator;
        $this->subeventRepository      = $subeventRepository;
        $this->skautIsCourseRepository = $skautIsCourseRepository;
    }

    /**
     * Vykreslí komponentu.
     */
    public function render() : void
    {
        $this->template->render(__DIR__ . '/templates/skaut_is_event_education_grid.latte');
    }

    /**
     * Vytvoří komponentu.
     */
    public function createComponentSkautIsEventEducationGrid(string $name) : void
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->subeventRepository->createQueryBuilder('s'));
        $grid->setDefaultSort(['name' => 'ASC']);
        $grid->setPagination(false);

        $grid->addColumnText('name', 'admin.configuration.skautis_event_education_subevent');

        $grid->addColumnText('skautIsCourses', 'admin.configuration.skautis_event_education_skaut_is_courses', 'skautIsCoursesText');

        $grid->addInlineEdit()->onControlAdd[]  = function (Container $container) : void {
            $container->addMultiSelect(
                'skautIsCourses',
                '',
                $this->skautIsCourseRepository->getSkautIsCoursesOptions()
            )
                    ->setAttribute('class', 'datagrid-multiselect');
        };
        $grid->getInlineEdit()->onSetDefaults[] = function (Container $container, Subevent $subevent) : void {
            $container->setDefaults(
                [
                        'skautIsCourses' => $this->skautIsCourseRepository->findSkautIsCoursesIds($subevent->getSkautIsCourses()),
                    ]
            );
        };
        $grid->getInlineEdit()->onSubmit[]      = [$this, 'edit'];
    }

    /**
     * Zpracuje úpravu propojení podakce s kurzy.
     *
     * @throws AbortException
     * @throws \Throwable
     */
    public function edit(int $id, \stdClass $values) : void
    {
        $subevent = $this->subeventRepository->findById($id);

        $subevent->setSkautIsCourses($this->skautIsCourseRepository->findSkautIsCoursesByIds($values['skautIsCourses']));

        $this->subeventRepository->save($subevent);

        $this->getPresenter()->flashMessage('admin.configuration.skautis_event_education_connection_saved', 'success');
        $this->redirect('this');
    }
}
