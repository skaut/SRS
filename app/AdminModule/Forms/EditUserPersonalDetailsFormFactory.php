<?php

declare(strict_types=1);

namespace App\AdminModule\Forms;

use App\Model\User\Repositories\UserRepository;
use App\Model\User\User;
use App\Services\FilesService;
use Doctrine\ORM\ORMException;
use Nette;
use Nette\Application\UI\Form;
use Nette\Utils\ImageException;
use Nextras\FormComponents\Controls\DateControl;
use stdClass;

use function basename;
use function getimagesizefromstring;
use function image_type_to_extension;
use function json_encode;

use const JSON_THROW_ON_ERROR;
use const UPLOAD_ERR_OK;

/**
 * Formulář pro úpravu osobních údajů externích lektorů.
 */
class EditUserPersonalDetailsFormFactory
{
    use Nette\SmartObject;

    /**
     * Upravovaný uživatel.
     */
    private ?User $user = null;

    public function __construct(
        private BaseFormFactory $baseFormFactory,
        private UserRepository $userRepository,
        private FilesService $filesService
    ) {
    }

    /**
     * Vytvoří formulář.
     */
    public function create(int $id): Form
    {
        $this->user = $this->userRepository->findById($id);

        $form = $this->baseFormFactory->create();

        $form->addHidden('id');

        $photoUpload = $form->addUpload('photo', 'admin.users.users_photo');
        $photoUpload->setHtmlAttribute('accept', 'image/*')
            ->setHtmlAttribute('data-show-preview', 'true')
            ->addCondition(Form::FILLED)
            ->addRule(Form::IMAGE, 'admin.users.users_photo_format');

        if ($this->user->getPhoto() !== null) {
            $photoUpload->setHtmlAttribute('data-delete-url', '?do=removePhoto')
                ->setHtmlAttribute('data-initial-preview', json_encode([$this->user->getPhoto()], JSON_THROW_ON_ERROR))
                ->setHtmlAttribute('data-initial-preview-show-delete', 'true')
                ->setHtmlAttribute('data-initial-preview-config', json_encode([['caption' => basename($this->user->getPhoto())]]));
        }

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

        $form->addText('phone', 'admin.users.users_phone')
            ->addCondition(Form::FILLED)
            ->addRule(Form::PATTERN, 'admin.users.users_phone_format', '^\d{9}$');

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
            'phone' => $this->user->getPhone(),
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
    public function processForm(Form $form, stdClass $values): void
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
        $this->user->setPhone($values->phone);
        $this->user->setBirthdate($values->birthdate);
        $this->user->setStreet($values->street);
        $this->user->setCity($values->city);
        $this->user->setPostcode($values->postcode);

        $photo = $values->photo;
        if ($photo->getError() === UPLOAD_ERR_OK) {
            $photoExtension = image_type_to_extension(getimagesizefromstring($photo->getContents())[2]);
            $photoName      = 'ext_' . $this->user->getId() . $photoExtension;

            $path = $this->filesService->save($photo, User::PHOTO_PATH, false, $photoName);
            $this->filesService->resizeAndCropImage($path, 135, 180);

            $this->user->setPhoto($path);
        }

        $this->userRepository->save($this->user);
    }
}
