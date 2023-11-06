<?php

declare(strict_types=1);

namespace App\WebModule\Forms;

use App\Model\Enums\Sex;
use App\Model\User\Repositories\UserRepository;
use App\Model\User\User;
use App\Services\SkautIsService;
use Nette;
use Nette\Application\UI\Form;
use Nextras\FormComponents\Controls\DateControl;
use Skaut\Skautis\Wsdl\WsdlException;
use stdClass;
use Tracy\Debugger;
use Tracy\ILogger;

use function property_exists;

/**
 * Formulář pro úpravu osobních údajů.
 */
class PersonalDetailsFormFactory
{
    use Nette\SmartObject;

    /**
     * Přihlášený uživatel.
     */
    private User $user;

    /** @var callable[] */
    public array $onSkautIsError = [];

    public function __construct(
        private readonly BaseFormFactory $baseFormFactory,
        private readonly UserRepository $userRepository,
        private readonly SkautIsService $skautIsService,
    ) {
    }

    /**
     * Vytvoří formulář.
     */
    public function create(User $user): Form
    {
        $this->user = $user;

        $form = $this->baseFormFactory->create();

        $inputSex = $form->addRadioList('sex', 'web.profile.personal_details.sex', Sex::getSexOptions());

        $inputFirstName = $form->addText('firstName', 'web.profile.personal_details.firstname')
            ->addRule(Form::FILLED, 'web.profile.personal_details.firstname_empty');

        $inputLastName = $form->addText('lastName', 'web.profile.personal_details.lastname')
            ->addRule(Form::FILLED, 'web.profile.personal_details.lastname_empty');

        $inputNickName = $form->addText('nickName', 'web.profile.personal_details.nickname');

        $inputBirthdateDate = new DateControl('web.profile.personal_details.birthdate');
        $inputBirthdateDate->addRule(Form::FILLED, 'web.profile.personal_details.birthdate_empty');
        $form->addComponent($inputBirthdateDate, 'birthdate');

        if ($this->user->isMember()) {
            $inputSex->setDisabled();
            $inputFirstName->setDisabled();
            $inputLastName->setDisabled();
            $inputNickName->setDisabled();
            $inputBirthdateDate->setDisabled();
        }

        $form->addText('email', 'web.profile.personal_details.email')
            ->setDisabled();

        $form->addText('phone', 'web.profile.personal_details.phone')
            ->setDisabled();

        $form->addText('street', 'web.profile.personal_details.street')
            ->addRule(Form::FILLED, 'web.profile.personal_details.street_empty')
            ->addRule(Form::PATTERN, 'web.profile.personal_details.street_format', '^(.*[^0-9]+) (([1-9][0-9]*)/)?([1-9][0-9]*[a-cA-C]?)$');

        $form->addText('city', 'web.profile.personal_details.city')
            ->addRule(Form::FILLED, 'web.profile.personal_details.city_empty');

        $form->addText('postcode', 'web.profile.personal_details.postcode')
            ->addRule(Form::FILLED, 'web.profile.personal_details.postcode_empty')
            ->addRule(Form::PATTERN, 'web.profile.personal_details.postcode_format', '^\d{3} ?\d{2}$');

        $form->addText('state', 'web.profile.personal_details.state')
            ->addRule(Form::FILLED, 'web.profile.personal_details.state_empty');

        $form->addSubmit('submit', 'web.profile.personal_details.update');

        $form->setDefaults([
            'sex' => $this->user->getSex(),
            'firstName' => $this->user->getFirstName(),
            'lastName' => $this->user->getLastName(),
            'nickName' => $this->user->getNickName(),
            'email' => $this->user->getEmail(),
            'phone' => $this->user->getPhone(),
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
     */
    public function processForm(Form $form, stdClass $values): void
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

        // aktualizace údajů ve skautIS, jen pokud nemá propojený účet
        if (! $this->user->isMember()) {
            try {
                $this->skautIsService->updatePersonBasic(
                    $this->user->getSkautISPersonId(),
                    $this->user->getSex(),
                    $this->user->getBirthdate(),
                    $this->user->getFirstName(),
                    $this->user->getLastName(),
                    $this->user->getNickName(),
                );

                $this->skautIsService->updatePersonAddress(
                    $this->user->getSkautISPersonId(),
                    $this->user->getStreet(),
                    $this->user->getCity(),
                    $this->user->getPostcode(),
                    $this->user->getState(),
                );
            } catch (WsdlException $ex) {
                Debugger::log($ex, ILogger::WARNING);
                $this->onSkautIsError();
            }
        }
    }
}
