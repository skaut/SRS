<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseFormFactory;
use App\Model\Enums\SkautIsEventType;
use App\Model\Settings\Commands\SetSettingIntValue;
use App\Model\Settings\Commands\SetSettingStringValue;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Settings;
use App\Model\SkautIs\Repositories\SkautIsCourseRepository;
use App\Model\SkautIs\SkautIsCourse;
use App\Model\Structure\Repositories\SubeventRepository;
use App\Services\CommandBus;
use App\Services\QueryBus;
use App\Services\SkautIsEventEducationService;
use App\Services\SkautIsEventGeneralService;
use Doctrine\ORM\NonUniqueResultException;
use Nette;
use Nette\Application\UI\Form;
use Nextras\FormsRendering\Renderers\Bs4FormRenderer;
use stdClass;
use Throwable;

use function assert;
use function count;

/**
 * Formulár pro nastavení propojení se skautIS akcí.
 */
class SkautIsEventFormFactory
{
    use Nette\SmartObject;

    public function __construct(
        private readonly BaseFormFactory $baseFormFactory,
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
        private readonly SkautIsCourseRepository $skautIsCourseRepository,
        private readonly SkautIsEventGeneralService $skautIsEventGeneralService,
        private readonly SkautIsEventEducationService $skautIsEventEducationService,
        private readonly SubeventRepository $subeventRepository,
    ) {
    }

    /**
     * Vytvoří formulář.
     *
     * @throws Throwable
     */
    public function create(): Form
    {
        $form = $this->baseFormFactory->create();

        $renderer = $form->getRenderer();
        assert($renderer instanceof Bs4FormRenderer);
        $renderer->wrappers['control']['container'] = 'div class="col-7"';
        $renderer->wrappers['label']['container']   = 'div class="col-5 col-form-label"';

        $eventTypeSelect = $form->addSelect(
            'skautisEventType',
            'admin.configuration.skautis_event_type',
            SkautIsEventType::getSkautIsEventTypesOptions(),
        );
        $eventTypeSelect->addCondition($form::EQUAL, SkautIsEventType::GENERAL)
            ->toggle('event-general');
        $eventTypeSelect->addCondition($form::EQUAL, SkautIsEventType::EDUCATION)
            ->toggle('event-education');

        $form->addSelect(
            'skautisEventGeneral',
            'admin.configuration.skautis_event',
            $this->skautIsEventGeneralService->getEventsOptions(),
        )
            ->setOption('id', 'event-general');

        $form->addSelect(
            'skautisEventEducation',
            'admin.configuration.skautis_event',
            $this->skautIsEventEducationService->getEventsOptions(),
        )
            ->setOption('id', 'event-education');

        $form->addSubmit('submit', 'admin.common.save');

        $form->setDefaults([
            'skautisEventType' => $this->queryBus->handle(new SettingStringValueQuery(Settings::SKAUTIS_EVENT_TYPE)),
        ]);

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     *
     * @throws NonUniqueResultException
     * @throws Throwable
     */
    public function processForm(Form $form, stdClass $values): void
    {
        $eventId   = null;
        $eventName = null;
        $eventType = $values->skautisEventType;

        switch ($eventType) {
            case SkautIsEventType::GENERAL:
                $eventId   = $values->skautisEventGeneral;
                $eventName = $this->skautIsEventGeneralService->getEventDisplayName($eventId);
                break;

            case SkautIsEventType::EDUCATION:
                $eventId   = $values->skautisEventEducation;
                $eventName = $this->skautIsEventEducationService->getEventDisplayName($eventId);

                $courses = $this->skautIsEventEducationService->getEventCourses($eventId);

                foreach ($courses as $course) {
                    $skautIsCourse = new SkautIsCourse();
                    $skautIsCourse->setSkautIsCourseId($course->ID);
                    $skautIsCourseName = $course->EventEducationType . (! empty($course->DisplayName) ? ' (' . $course->DisplayName . ')' : '');
                    $skautIsCourse->setName($skautIsCourseName);
                    $this->skautIsCourseRepository->save($skautIsCourse);
                }

                if (count($courses) === 1 && ! $this->subeventRepository->explicitSubeventsExists()) {
                    $subevent = $this->subeventRepository->findImplicit();
                    $subevent->setSkautIsCourses($this->skautIsCourseRepository->findAll());
                    $this->subeventRepository->save($subevent);
                }

                break;
        }

        $this->commandBus->handle(new SetSettingStringValue(Settings::SKAUTIS_EVENT_TYPE, $eventType));

        if ($eventId !== null) {
            $this->commandBus->handle(new SetSettingIntValue(Settings::SKAUTIS_EVENT_ID, $eventId));
            $this->commandBus->handle(new SetSettingStringValue(Settings::SKAUTIS_EVENT_NAME, $eventName));
        }
    }
}
