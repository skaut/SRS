<?php

declare(strict_types=1);

namespace App\WebModule\Forms;

use App\Model\Enums\Sex;
use App\Model\User\User;
use App\Model\User\UserRepository;
use App\Services\SkautIsService;
use Doctrine\ORM\ORMException;
use Nette;
use Nette\Application\UI\Form;
use Nextras\FormComponents\Controls\DateControl;
use Skautis\Wsdl\WsdlException;
use stdClass;
use Tracy\Debugger;
use Tracy\ILogger;
use function property_exists;

/**
 * Formulář pro úpravu osobních údajů.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class PersonalDetailsFormFactory
{
    use Nette\SmartObject;

    /**
     * Přihlášený uživatel.
     *
     * @var User
     */
    private $user;

    /** @var callable */
    public $onSkautIsError;

    /** @var BaseFormFactory */
    private $baseFormFactory;

    /** @var UserRepository */
    private $userRepository;

    /** @var SkautIsService */
    private $skautIsService;

    public function __construct(BaseFormFactory $baseFormFactory, UserRepository $userRepository, SkautIsService $skautIsService)
    {
        $this->baseFormFactory = $baseFormFactory;
        $this->userRepository  = $userRepository;
        $this->skautIsService  = $skautIsService;
    }

    /**
     * Vytvoří formulář.
     */
    public function create(int $id) : Form
    {
        $this->user = $this->userRepository->findById($id);

        $form = $this->baseFormFactory->create();

        $form->addHidden('id');

        $inputSex = $form->addRadioList('sex', 'web.profile.sex', Sex::getSexOptions());
        $inputSex->getSeparatorPrototype()->setName('');

        $inputFirstName = $form->addText('firstName', 'web.profile.firstname')
            ->addRule(Form::FILLED, 'web.profile.firstname_empty');

        $inputLastName = $form->addText('lastName', 'web.profile.lastname')
            ->addRule(Form::FILLED, 'web.profile.lastname_empty');

        $inputNickName = $form->addText('nickName', 'web.profile.nickname');

        $inputBirthdateDate = new DateControl('web.profile.birthdate');
        $inputBirthdateDate->addRule(Form::FILLED, 'web.profile.birthdate_empty');
        $form->addComponent($inputBirthdateDate, 'birthdate');

        if ($this->user->isMember()) {
            $inputSex->setDisabled();
            $inputFirstName->setDisabled();
            $inputLastName->setDisabled();
            $inputNickName->setDisabled();
            $inputBirthdateDate->setDisabled();
        }

        $form->addText('email', 'web.application_content.email')
            ->addRule(Form::FILLED)
            ->setDisabled();

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
            'email' => $this->user->getEmail(),
            'birthdate' => $this->user->getBirthdate(),
            'street' => $this->user->getStreet(),
            'city' => $this->user->getCity(),
            'postcode' => $this->user->getPostcode(),
            'state' => $this->user->getState(),
        ]);

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     *
     * @throws ORMException
     */
    public function processForm(Form $form, stdClass $values) : void
    {
        if (property_exists($values, 'sex')) {
            $this->user->setSex($values->sex);
        }
        if (property_exists($values, 'firstName')) {
            $this->user->setFirstName($values->firstName);
        }
        if (property_exists($values, 'lastName')) {
            $this->user->setLastName($values->lastName);
        }
        if (property_exists($values, 'nickName')) {
            $this->user->setNickName($values->nickName);
        }
        if (property_exists($values, 'birthdate')) {
            $this->user->setBirthdate($values->birthdate);
        }

        $this->user->setStreet($values->street);
        $this->user->setCity($values->city);
        $this->user->setPostcode($values->postcode);
        $this->user->setState($values->state);

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
            Debugger::log($ex, ILogger::WARNING);
            $this->onSkautIsError();
        }
    }
}
