<?php

namespace App\WebModule\Forms;

use App\Model\User\User;
use App\Model\User\UserRepository;
use Nette;
use Nette\Application\UI\Form;


/**
 * Formulář pro zadání doplňujících informací.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class AdditionalInformationForm extends Nette\Object
{
    /** @var User */
    private $user;

    /** @var BaseForm */
    private $baseFormFactory;

    /** @var UserRepository */
    private $userRepository;


    /**
     * AdditionalInformationForm constructor.
     * @param BaseForm $baseFormFactory
     * @param UserRepository $userRepository
     */
    public function __construct(BaseForm $baseFormFactory, UserRepository $userRepository)
    {
        $this->baseFormFactory = $baseFormFactory;
        $this->userRepository = $userRepository;
    }

    /**
     * @param $id
     * @return Form
     */
    public function create($id)
    {
        $this->user = $this->userRepository->findById($id);

        $form = $this->baseFormFactory->create();

        $form->addHidden('id');

        $form->addTextArea('about', 'web.profile.about_me');

        if ($this->user->hasDisplayArrivalDepartureRole()) {
            $form->addDateTimePicker('arrival', 'web.profile.arrival');
            $form->addDateTimePicker('departure', 'web.profile.departure');
        }

        $form->addSubmit('submit', 'web.profile.update_additional_information');

        $form->setDefaults([
            'id' => $id,
            'about' => $this->user->getAbout(),
            'arrival' => $this->user->getArrival(),
            'departure' => $this->user->getDeparture()
        ]);

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * @param Form $form
     * @param \stdClass $values
     */
    public function processForm(Form $form, \stdClass $values)
    {
        $this->user->setAbout($values['about']);

        if (array_key_exists('arrival', $values))
            $this->user->setArrival($values['arrival']);
        if (array_key_exists('departure', $values))
            $this->user->setDeparture($values['departure']);

        $this->userRepository->save($this->user);
    }
}
