<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\AdminModule\Forms\BaseFormFactory;
use App\Model\Enums\SkautIsEventType;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Model\SkautIs\SkautIsCourse;
use App\Model\SkautIs\SkautIsCourseRepository;
use App\Model\Structure\SubeventRepository;
use App\Services\SettingsService;
use App\Services\SkautIsEventEducationService;
use App\Services\SkautIsEventGeneralService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Nette;
use stdClass;
use Throwable;
use function count;

/**
 * Formulár pro nastavení propojení se skautIS akcí.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SkautIsEventFormFactory
{
    use Nette\SmartObject;

    /** @var BaseFormFactory */
    private $baseFormFactory;

    /** @var SettingsService */
    private $settingsService;

    /** @var SkautIsCourseRepository */
    private $skautIsCourseRepository;

    /** @var SkautIsEventGeneralService */
    private $skautIsEventGeneralService;

    /** @var SkautIsEventEducationService */
    private $skautIsEventEducationService;

    /** @var SubeventRepository */
    private $subeventRepository;

    public function __construct(
        BaseFormFactory $baseForm,
        SettingsService $settingsService,
        SkautIsCourseRepository $skautIsCourseRepository,
        SkautIsEventGeneralService $skautIsEventGeneralService,
        SkautIsEventEducationService $skautIsEventEducationService,
        SubeventRepository $subeventRepository
    ) {
        $this->baseFormFactory              = $baseForm;
        $this->settingsService              = $settingsService;
        $this->skautIsCourseRepository      = $skautIsCourseRepository;
        $this->skautIsEventGeneralService   = $skautIsEventGeneralService;
        $this->skautIsEventEducationService = $skautIsEventEducationService;
        $this->subeventRepository           = $subeventRepository;
    }

    /**
     * Vytvoří formulář.
     *
     * @throws SettingsException
     * @throws Throwable
     */
    public function create() : BaseForm
    {
        $form = $this->baseFormFactory->create();

        /** @var Bs3FormRenderer $renderer */
        $renderer                                   = $form->getRenderer();
        $renderer->wrappers['control']['container'] = 'div class="col-sm-7 col-xs-7"';
        $renderer->wrappers['label']['container']   = 'div class="col-sm-5 col-xs-5 control-label"';

        $eventTypeSelect = $form->addSelect(
            'skautisEventType',
            'admin.configuration.skautis_event_type',
            SkautIsEventType::getSkautIsEventTypesOptions()
        );
        $eventTypeSelect->addCondition($form::EQUAL, SkautIsEventType::GENERAL)
            ->toggle('event-general');
        $eventTypeSelect->addCondition($form::EQUAL, SkautIsEventType::EDUCATION)
            ->toggle('event-education');

        $form->addSelect(
            'skautisEventGeneral',
            'admin.configuration.skautis_event',
            $this->skautIsEventGeneralService->getEventsOptions()
        )
            ->setOption('id', 'event-general');

        $form->addSelect(
            'skautisEventEducation',
            'admin.configuration.skautis_event',
            $this->skautIsEventEducationService->getEventsOptions()
        )
            ->setOption('id', 'event-education');

        $form->addSubmit('submit', 'admin.common.save');

        $form->setDefaults([
            'skautisEventType' => $this->settingsService->getValue(Settings::SKAUTIS_EVENT_TYPE),
        ]);

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     *
     * @throws SettingsException
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Throwable
     */
    public function processForm(BaseForm $form, stdClass $values) : void
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
                    $subevent->setSkautIsCourses(new ArrayCollection($this->skautIsCourseRepository->findAll()));
                    $this->subeventRepository->save($subevent);
                }

                break;
        }

        $this->settingsService->setValue(Settings::SKAUTIS_EVENT_TYPE, $eventType);

        if ($eventId === null) {
            return;
        }

        $this->settingsService->setIntValue(Settings::SKAUTIS_EVENT_ID, $eventId);
        $this->settingsService->setValue(Settings::SKAUTIS_EVENT_NAME, $eventName);
    }
}
