<?php

namespace App\WebModule\Forms;

use App\Model\Enums\Sex;
use App\Model\User\User;
use App\Model\User\UserRepository;
use App\Services\SkautIsService;
use Nette;
use Nette\Application\UI\Form;
use Skautis\Wsdl\WsdlException;


class PersonalDetailsForm extends Nette\Object
{
    /** @var User */
    private $user;

    public $onSkautIsError;

    /** @var BaseForm */
    private $baseFormFactory;

    /** @var UserRepository */
    private $userRepository;

    /** @var SkautIsService */
    private $skautIsService;


    public function __construct(BaseForm $baseFormFactory, UserRepository $userRepository, SkautIsService $skautIsService)
    {
        $this->baseFormFactory = $baseFormFactory;
        $this->userRepository = $userRepository;
        $this->skautIsService = $skautIsService;
    }

    public function create($id)
    {
        $this->user = $this->userRepository->findById($id);

        $form = $this->baseFormFactory->create();

        $form->addHidden('id');

        $inputSex = $form->addRadioList('sex', 'web.profile.sex', Sex::getSexOptions());
        $inputSex->getSeparatorPrototype()->setName(null);

        $inputFirstName = $form->addText('firstName', 'web.profile.firstname')
            ->addRule(Form::FILLED, 'web.profile.firstname_empty');

        $inputLastName = $form->addText('lastName', 'web.profile.lastname')
            ->addRule(Form::FILLED, 'web.profile.lastname_empty');

        $inputNickName = $form->addText('nickName', 'web.profile.nickname');

        $inputBirthdate = $form->addDatePicker('birthdate', 'web.profile.birthdate')
            ->addRule(Form::FILLED, 'web.profile.birthdate_empty');

        if ($this->user->isMember()) {
            $inputSex->setDisabled();
            $inputFirstName->setDisabled();
            $inputLastName->setDisabled();
            $inputNickName->setDisabled();
            $inputBirthdate->setDisabled();
        }

        $form->addText('street', 'web.profile.street')
            ->addRule(Form::FILLED, 'web.profile.street_empty')
            ->addRule(Form::PATTERN, 'web.profile.street_format', '^(.*[^0-9]+) (([1-9][0-9]*)/)?([1-9][0-9]*[a-cA-C]?)$');

        $form->addText('city', 'web.profile.city')
            ->addRule(Form::FILLED, 'web.profile.city_empty');

        $form->addText('postcode', 'web.profile.postcode')
            ->addRule(Form::FILLED, 'web.profile.postcode_empty')
            ->addRule(Form::PATTERN, 'web.profile.postcode_format', '^\d{3} ?\d{2}$');

        $form->addText('state', 'web.profile.state')
            ->addRule(Form::FILLED, 'web.profile.state_empty');

        $form->addSubmit('submit', 'web.profile.update_personal_details');

        $form->setDefaults([
            'id' => $id,
            'sex' => $this->user->getSex(),
            'firstName' => $this->user->getFirstName(),
            'lastName' => $this->user->getLastName(),
            'nickName' => $this->user->getNickName(),
            'birthdate' => $this->user->getBirthdate(),
            'street' => $this->user->getStreet(),
            'city' => $this->user->getCity(),
            'postcode' => $this->user->getPostcode(),
            'state' => $this->user->getState()
        ]);

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    public function processForm(Form $form, \stdClass $values)
    {
        if (array_key_exists('sex', $values))
            $this->user->setSex($values['sex']);
        if (array_key_exists('firstName', $values))
            $this->user->setFirstName($values['firstName']);
        if (array_key_exists('lastName', $values))
            $this->user->setLastName($values['lastName']);
        if (array_key_exists('nickName', $values))
            $this->user->setNickName($values['nickName']);
        if (array_key_exists('birthdate', $values))
            $this->user->setBirthdate($values['birthdate']);

        $this->user->setStreet($values['street']);
        $this->user->setCity($values['city']);
        $this->user->setPostcode($values['postcode']);
        $this->user->setState($values['state']);

        $this->userRepository->save($this->user);

        try {
            $this->skautIsService->updatePersonBasic(
                $this->user->getSkautISPersonId(),
                $this->user->getSex(),
                $this->user->getBirthdate(),
                $this->user->getFirstName(),
                $this->user->getLastName(),
                $this->user->getNickName()
            );

            $this->skautIsService->updatePersonAddress(
                $this->user->getSkautISPersonId(),
                $this->user->getStreet(),
                $this->user->getCity(),
                $this->user->getPostcode(),
                $this->user->getState()
            );
        } catch (WsdlException $ex) {
            $this->onSkautIsError();
        }
    }
}
