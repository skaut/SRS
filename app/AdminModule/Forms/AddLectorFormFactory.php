<?php

declare(strict_types=1);

namespace App\AdminModule\Forms;

use App\Model\Acl\Role;
use App\Model\Acl\RoleRepository;
use App\Model\User\User;
use App\Model\User\UserRepository;
use App\Services\FilesService;
use DateTimeImmutable;
use Doctrine\ORM\ORMException;
use Exception;
use Nette;
use Nette\Application\UI\Form;
use Nette\Http\FileUpload;
use Nextras\FormComponents\Controls\DateControl;
use stdClass;
use function getimagesizefromstring;
use function image_type_to_extension;
use const UPLOAD_ERR_OK;

/**
 * Formulář pro vytvoření externího lektora.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class AddLectorFormFactory
{
    use Nette\SmartObject;

    /** @var BaseFormFactory */
    private $baseFormFactory;

    /** @var UserRepository */
    private $userRepository;

    /** @var RoleRepository */
    private $roleRepository;

    /** @var FilesService */
    private $filesService;

    public function __construct(
        BaseFormFactory $baseFormFactory,
        UserRepository $userRepository,
        RoleRepository $roleRepository,
        FilesService $filesService
    ) {
        $this->baseFormFactory = $baseFormFactory;
        $this->userRepository  = $userRepository;
        $this->roleRepository  = $roleRepository;
        $this->filesService    = $filesService;
    }

    /**
     * Vytvoří formulář.
     */
    public function create() : Form
    {
        $form = $this->baseFormFactory->create();

        $form->addUpload('photo', 'admin.users.users_photo')
            ->setHtmlAttribute('accept', 'image/*')
            ->addCondition(Form::FILLED)
            ->addRule(Form::IMAGE, 'admin.users.users_photo_format');

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

        $form->addTextArea('about', 'admin.users.users_about_me');

        $form->addTextArea('privateNote', 'admin.users.users_private_note');

        $form->addSubmit('submit', 'admin.common.save');

        $form->addSubmit('cancel', 'admin.common.cancel')
            ->setValidationScope([])
            ->setHtmlAttribute('class', 'btn btn-warning');

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     *
     * @throws Nette\Utils\UnknownImageFileException
     * @throws ORMException
     * @throws Exception
     */
    public function processForm(Form $form, stdClass $values) : void
    {
        if ($form->isSubmitted() === $form['cancel']) {
            return;
        }

        $user = new User();

        $user->setExternalLector(true);
        $user->setFirstName($values->firstName);
        $user->setLastName($values->lastName);
        $user->setNickName($values->nickName);
        $user->setDegreePre($values->degreePre);
        $user->setDegreePost($values->degreePost);
        $user->setEmail($values->email);
        $user->setBirthdate($values->birthdate);
        $user->setStreet($values->street);
        $user->setCity($values->city);
        $user->setPostcode($values->postcode);
        $user->setAbout($values->about);
        $user->setNote($values->privateNote);

        $user->addRole($this->roleRepository->findBySystemName(Role::LECTOR));

        $this->userRepository->save($user);

        /** @var FileUpload $photo */
        $photo = $values->photo;
        if ($photo->getError() == UPLOAD_ERR_OK) {
            $photoExtension = image_type_to_extension(getimagesizefromstring($photo->getContents())[2]);
            $photoName      = 'ext_' . $user->getId() . $photoExtension;

            $this->filesService->save($photo, User::PHOTO_PATH . '/' . $photoName);
            $this->filesService->resizeAndCropImage(User::PHOTO_PATH . '/' . $photoName, 135, 180);

            $user->setPhoto($photoName);
        }

        $this->userRepository->save($user);
    }
}
