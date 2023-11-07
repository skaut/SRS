<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Components;

use App\Model\SkautIs\Repositories\SkautIsCourseRepository;
use App\Model\Structure\Repositories\SubeventRepository;
use App\Model\Structure\Subevent;
use Nette\Application\UI\Control;
use Nette\Forms\Container;
use Nette\Localization\Translator;
use stdClass;
use Throwable;
use Ublaboo\DataGrid\DataGrid;

/**
 * Komponenta pro nastavení propojení se vzdělávací akcí.
 */
class SkautIsEventEducationGridControl extends Control
{
    public function __construct(
        private readonly Translator $translator,
        private readonly SubeventRepository $subeventRepository,
        private readonly SkautIsCourseRepository $skautIsCourseRepository,
    ) {
    }

    /**
     * Vykreslí komponentu.
     */
    public function render(): void
    {
        $this->template->setFile(__DIR__ . '/templates/skaut_is_event_education_grid.latte');
        $this->template->render();
    }

    /**
     * Vytvoří komponentu.
     */
    public function createComponentSkautIsEventEducationGrid(string $name): void
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->subeventRepository->createQueryBuilder('s'));
        $grid->setDefaultSort(['name' => 'ASC']);
        $grid->setPagination(false);

        $grid->addColumnText('name', 'admin.configuration.skautis_event_education_subevent');

        $grid->addColumnText('skautIsCourses', 'admin.configuration.skautis_event_education_skaut_is_courses', 'skautIsCoursesText');

        $grid->addInlineEdit()->onControlAdd[]  = function (Container $container): void {
            $container->addMultiSelect(
                'skautIsCourses',
                '',
                $this->skautIsCourseRepository->getSkautIsCoursesOptions(),
            )
                ->setHtmlAttribute('class', 'datagrid-multiselect');
        };
        $grid->getInlineEdit()->onSetDefaults[] = function (Container $container, Subevent $subevent): void {
            $container->setDefaults([
                'skautIsCourses' => $this->skautIsCourseRepository->findSkautIsCoursesIds($subevent->getSkautIsCourses()),
            ]);
        };
        $grid->getInlineEdit()->onSubmit[]      = [$this, 'edit'];
    }

    /**
     * Zpracuje úpravu propojení podakce s kurzy.
     *
     * @throws Throwable
     */
    public function edit(string $id, stdClass $values): void
    {
        $subevent = $this->subeventRepository->findById((int) $id);

        $subevent->setSkautIsCourses($this->skautIsCourseRepository->findSkautIsCoursesByIds($values->skautIsCourses));

        $this->subeventRepository->save($subevent);

        $this->getPresenter()->flashMessage('admin.configuration.skautis_event_education_connection_saved', 'success');
        $this->getPresenter()->redrawControl('flashes');
    }
}
