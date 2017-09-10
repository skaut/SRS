<?php

namespace App\WebModule\Forms;

use App\Model\ACL\RoleRepository;
use App\Model\Enums\ApplicationStates;
use App\Model\Mailing\Template;
use App\Model\Mailing\TemplateVariable;
use App\Model\Program\ProgramRepository;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsRepository;
use App\Model\Structure\SubeventRepository;
use App\Model\User\Application;
use App\Model\User\User;
use App\Model\User\UserRepository;
use App\Services\MailService;
use Doctrine\Common\Collections\ArrayCollection;
use Nette;
use Nette\Application\UI\Form;


/**
 * Formulář pro změnu podakcí.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SubeventsForm extends Nette\Object
{
    /**
     * Přihlášený uživatel.
     * @var User
     */
    private $user;

    /** @var BaseForm */
    private $baseFormFactory;

    /** @var UserRepository */
    private $userRepository;

    /** @var SubeventRepository */
    private $subeventRepository;

    /** @var ProgramRepository */
    private $programRepository;

    /** @var SettingsRepository */
    private $settingsRepository;

    /** @var MailService */
    private $mailService;


    /**
     * SubeventsForm constructor.
     * @param BaseForm $baseFormFactory
     * @param UserRepository $userRepository
     * @param SubeventRepository $subeventRepository
     * @param ProgramRepository $programRepository
     * @param SettingsRepository $settingsRepository
     * @param MailService $mailService
     */
    public function __construct(BaseForm $baseFormFactory, UserRepository $userRepository,
                                SubeventRepository $subeventRepository, ProgramRepository $programRepository,
                                SettingsRepository $settingsRepository, MailService $mailService)
    {
        $this->baseFormFactory = $baseFormFactory;
        $this->userRepository = $userRepository;
        $this->subeventRepository = $subeventRepository;
        $this->programRepository = $programRepository;
        $this->settingsRepository = $settingsRepository;
        $this->mailService = $mailService;
    }

    /**
     * Vytvoří formulář.
     * @param $id
     * @param $enabled
     * @return Form
     */
    public function create($id, $enabled)
    {
        $this->user = $this->userRepository->findById($id);

        $form = $this->baseFormFactory->create();

        $form->addHidden('id');

        $this->addSubeventsSelect($form, $enabled);

        $submitButton = $form->addSubmit('submit', 'web.profile.add_subevents');

        if ($enabled) {
            $submitButton
                ->setAttribute('data-toggle', 'confirmation')
                ->setAttribute('data-content', $form->getTranslator()->translate('web.profile.change_subevents_confirm'));
        } else {
            $submitButton
                ->setDisabled()
                ->setAttribute('data-toggle', 'tooltip')
                ->setAttribute('title', $form->getTranslator()->translate('web.profile.change_subevents_disabled'));
        }

        $form->setDefaults([
            'id' => $id
        ]);

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     * @param Form $form
     * @param \stdClass $values
     */
    public function processForm(Form $form, \stdClass $values)
    {
        $selectedSubevents = $this->subeventRepository->findSubeventsByIds($values['subevents']);

        $application = new Application();

        $application->setUser($this->user);
        $application->setSubevents($selectedSubevents);
        $application->setApplicationDate(new \DateTime());
        $application->setState(ApplicationStates::WAITING_FOR_PAYMENT); //TODO PAID u neplacene

        $this->applicationRepository->save($application);

        $this->programRepository->updateUserPrograms($this->user);

        $this->userRepository->save($this->user);

        $subeventsNames = "";
        $first = TRUE;
        foreach ($selectedSubevents as $subevent) {
            if ($first) {
                $subeventsNames = $subevent->getName();
                $first = FALSE;
            }
            else {
                $subeventsNames .= ', ' . $subevent->getName();
            }
        }

        $this->mailService->sendMailFromTemplate(new ArrayCollection(), new ArrayCollection([$this->user]), '', Template::SUBEVENT_CHANGED, [
            TemplateVariable::SEMINAR_NAME => $this->settingsRepository->getValue(Settings::SEMINAR_NAME),
            TemplateVariable::USERS_SUBEVENTS => $subeventsNames
        ]);
    }

    /**
     * Přidá select pro výběr podakcí.
     * @param Form $form
     * @param $enabled
     */
    private function addSubeventsSelect(Form $form, $enabled)
    {
//        $subeventsSelect = $form->addMultiSelect('subevents', 'web.profile.subevents')->setItems(
//            $this->subeventRepository->getUsersNotRegisteredOptionsWithCapacity($this->user)
//        )
//            ->addRule(Form::FILLED, 'web.profile.subevents_empty')
//            ->addRule([$this, 'validateSubeventsCapacities'], 'web.profile.subevents_capacity_occupied')
//            ->setDisabled(!$enabled);
//
//        //generovani chybovych hlasek pro vsechny kombinace podakci
//        foreach ($this->subeventRepository->findAllExplicitOrderedByName() as $subevent) {
//            $incompatibleSubevents = $subevent->getIncompatibleSubevent();
//            if (count($incompatibleSubevents) > 0) {
//                $messageThis = $subevent->getName();
//
//                $first = TRUE;
//                $messageOthers = "";
//                foreach ($incompatibleSubevents as $incompatibleSubevent) {
//                    if ($first)
//                        $messageOthers .= $incompatibleSubevent->getName();
//                    else
//                        $messageOthers .= ", " . $incompatibleSubevent->getName();
//                    $first = FALSE;
//                }
//                $subeventsSelect->addRule([$this, 'validateSubeventsIncompatible'],
//                    $form->getTranslator()->translate('web.profile.incompatible_subevents_selected', NULL,
//                        ['subevent' => $messageThis, 'incompatibleSubevents' => $messageOthers]
//                    ),
//                    [$subevent]
//                );
//            }
//
//            $requiredSubevents = $subevent->getRequiredSubeventsTransitive();
//            if (count($requiredSubevents) > 0) {
//                $messageThis = $subevent->getName();
//
//                $first = TRUE;
//                $messageOthers = "";
//                foreach ($requiredSubevents as $requiredSubevent) {
//                    if ($first)
//                        $messageOthers .= $requiredSubevent->getName();
//                    else
//                        $messageOthers .= ", " . $requiredSubevent->getName();
//                    $first = FALSE;
//                }
//                $subeventsSelect->addRule([$this, 'validateSubeventsRequired'],
//                    $form->getTranslator()->translate('web.profile.required_subevents_not_selected', NULL,
//                        ['subevent' => $messageThis, 'requiredSubevents' => $messageOthers]
//                    ),
//                    [$subevent]
//                );
//            }
//        }
    }

    /**
     * Ověří kapacitu podakcí.
     * @param $field
     * @param $args
     * @return bool
     */
    public function validateSubeventsCapacities($field, $args)
    {
        foreach ($this->subeventRepository->findSubeventsByIds($field->getValue()) as $subevent) {
            if ($subevent->hasLimitedCapacity()) {
                if ($this->subeventRepository->countUnoccupiedInSubevent($subevent) < 1)
                    return FALSE;
            }
        }
        return TRUE;
    }

    /**
     * Ověří kompatibilitu podakcí.
     * @param $field
     * @param $args
     * @return bool
     */
    public function validateSubeventsIncompatible($field, $args)
    {
        //TODO kontrola stavajicich
        $selectedRolesIds = $field->getValue();
        $testRole = $args[0];

        if (!in_array($testRole->getId(), $selectedRolesIds))
            return TRUE;

        foreach ($testRole->getIncompatibleRoles() as $incompatibleRole) {
            if (in_array($incompatibleRole->getId(), $selectedRolesIds))
                return FALSE;
        }

        return TRUE;
    }

    /**
     * Ověří výběr vyžadovaných podakcí.
     * @param $field
     * @param $args
     * @return bool
     */
    public function validateSubeventsRequired($field, $args)
    {
        //TODO kontrola stavajicich
        $selectedRolesIds = $field->getValue();
        $testRole = $args[0];

        if (!in_array($testRole->getId(), $selectedRolesIds))
            return TRUE;

        foreach ($testRole->getRequiredRolesTransitive() as $requiredRole) {
            if (!in_array($requiredRole->getId(), $selectedRolesIds))
                return FALSE;
        }

        return TRUE;
    }
}

