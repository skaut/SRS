<?php

declare(strict_types=1);

namespace App\AdminModule\Forms;

use App\Model\User\Repositories\UserRepository;
use App\Model\User\User;
use App\Services\FilesService;
use Doctrine\ORM\ORMException;
use Nette;
use Nette\Application\UI\Form;
use Nette\Http\FileUpload;
use Nette\Utils\ImageException;
use Nextras\FormComponents\Controls\DateControl;
use stdClass;
use function getimagesizefromstring;
use function image_type_to_extension;
use function property_exists;
use const UPLOAD_ERR_OK;

/**
 * Formulář pro úpravu osobních údajů externích lektorů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class EditUserPersonalDetailsFormFactory
{
    use Nette\SmartObject;

    /**
     * Upravovaný uživatel.
     */
    private ?User $user = null;

    private BaseFormFactory $baseFormFactory;

    private UserRepository $userRepository;

    private FilesService $filesService;

    public function __construct(BaseFormFactory $baseFormFactory, UserRepository $userRepository, FilesService $filesService)
    {
        $this->baseFormFactory = $baseFormFactory;
        $this->userRepository  = $userRepository;
        $this->filesService    = $filesService;
    }

    /**
     * Vytvoří formulář.
     */
    public function create(int $id) : Form
    {
        $this->user = $this->userRepository->findById($id);

        $form = $this->baseFormFactory->create();

        $form->addHidden('id');

        $form->addUpload('newPhoto', 'admin.users.users_new_photo')
            ->setHtmlAttribute('accept', 'image/*')
            ->setOption('id', 'new-photo')
            ->addCondition(Form::FILLED)
            ->addRule(Form::IMAGE, 'admin.users.users_photo_format')
            ->toggle('remove-photo', false);

        $form->addCheckbox('removePhoto', 'admin.users.users_remove_photo')
            ->setOption('id', 'remove-photo')
            ->setDisabled($this->user->getPhoto() === null)
            ->addCondition(Form::FILLED)
            ->toggle('new-photo', false);

        $form->addText('firstName', 'admin.users.users_firstname')
            ->addRule(Form::FILLED, 'admin.users.users_firstname_empty');

        $form->addText('lastName', 'admin.users.users_lastname')
            ->addRule(Form::FILLED, 'admin.users.users_lastname_empty');

        $form->addText('nickName', 'admin.users.users_nickname');

        $form->addText('degreePre', 'admin.users.users_degree_pre');

        $form->addText('degreePost', 'admin.users.users_degree_post');

        $form->addText('email', 'admin.users.users_email')
            ->addCondition(Form::FILLED)
            ->addRule(Form::EMAIL, 'admin.users.users_email_format');

        $birthdateDate = new DateControl('admin.users.users_birthdate');
        $form->addComponent($birthdateDate, 'birthdate');

        $form->addText('street', 'admin.users.users_street')
            ->addCondition(Form::FILLED)
            ->addRule(Form::PATTERN, 'web.application_content.street_format', '^(.*[^0-9]+) (([1-9][0-9]*)/)?([1-9][0-9]*[a-cA-C]?)$');

        $form->addText('city', 'admin.users.users_city');

        $form->addText('postcode', 'admin.users.users_postcode')
            ->addCondition(Form::FILLED)
            ->addRule(Form::PATTERN, 'web.application_content.postcode_format', '^\d{3} ?\d{2}$');

        $form->addSubmit('submit', 'admin.common.save');

        $form->addSubmit('cancel', 'admin.common.cancel')
            ->setValidationScope([])
            ->setHtmlAttribute('class', 'btn btn-warning');

        $form->setDefaults([
            'id' => $id,
            'firstName' => $this->user->getFirstName(),
            'lastName' => $this->user->getLastName(),
            'nickName' => $this->user->getNickName(),
            'degreePre' => $this->user->getDegreePre(),
            'degreePost' => $this->user->getDegreePost(),
            'email' => $this->user->getEmail(),
            'birthdate' => $this->user->getBirthdate(),
            'street' => $this->user->getStreet(),
            'city' => $this->user->getCity(),
            'postcode' => $this->user->getPostcode(),
        ]);

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     *
     * @throws Nette\Utils\UnknownImageFileException
     * @throws ORMException
     * @throws ImageException
     */
    public function processForm(Form $form, stdClass $values) : void
    {
        if ($form->isSubmitted() === $form['cancel']) {
            return;
        }

        $this->user->setFirstName($values->firstName);
        $this->user->setLastName($values->lastName);
        $this->user->setNickName($values->nickName);
        $this->user->setDegreePre($values->degreePre);
        $this->user->setDegreePost($values->degreePost);
        $this->user->setEmail($values->email);
        $this->user->setBirthdate($values->birthdate);
        $this->user->setStreet($values->street);
        $this->user->setCity($values->city);
        $this->user->setPostcode($values->postcode);

        if (property_exists($values, 'removePhoto') && $values->removePhoto) {
            $this->user->setPhoto(null);
        } elseif (property_exists($values, 'newPhoto')) {
            /** @var FileUpload $photo */
            $photo = $values->newPhoto;
            if ($photo->getError() == UPLOAD_ERR_OK) {
                $photoExtension = image_type_to_extension(getimagesizefromstring($photo->getContents())[2]);
                $photoName      = 'ext_' . $this->user->getId() . $photoExtension;

                $this->filesService->save($photo, User::PHOTO_PATH . '/' . $photoName);
                $this->filesService->resizeAndCropImage(User::PHOTO_PATH . '/' . $photoName, 135, 180);

                $this->user->setPhoto($photoName);
            }
        }

        $this->userRepository->save($this->user);
    }
}
