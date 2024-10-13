<?php

declare(strict_types=1);

namespace App\AdminModule\Forms;

use App\Model\User\Repositories\UserRepository;
use App\Model\User\User;
use App\Services\ApplicationService;
use Contributte\Translation\Translator;
use Nette;
use Nette\Application\UI\Form;
use Nette\Utils\ImageException;
use stdClass;

/**
 * Formulář pro předání registrace jinému uživateli.
 */
class EditUserTransferFormFactory
{
    use Nette\SmartObject;

    /**
     * Upravovaný uživatel.
     */
    private User|null $user = null;

    public function __construct(
        private readonly BaseFormFactory $baseFormFactory,
        private readonly UserRepository $userRepository,
        private readonly ApplicationService $applicationService,
        private readonly Translator $translator,
    ) {
    }

    public function create(int $id): Form
    {
        $this->user = $this->userRepository->findById($id);

        $form = $this->baseFormFactory->create();

        $form->addSelect('targetUser', 'admin.users.users_target_user', $this->userRepository->getUsersOptions(true))
            ->addRule(Form::NOT_EQUAL, 'admin.users.users_target_user_empty', 0)
            ->setHtmlAttribute('data-live-search', 'true');

        $form->addSubmit('submit', 'admin.users.users_transfer')
            ->setHtmlAttribute('class', 'btn btn-danger')
            ->setHtmlAttribute('data-toggle', 'confirmation')
            ->setHtmlAttribute('data-content', $this->translator->translate('admin.users.users_transfer_confirm'));

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     *
     * @throws Nette\Utils\UnknownImageFileException
     * @throws ImageException
     */
    public function processForm(Form $form, stdClass $values): void
    {
//        $this->user->setFirstName($values->firstName);
//        $this->user->setLastName($values->lastName);
//        $this->user->setNickName($values->nickName);
//        $this->user->setDegreePre($values->degreePre);
//        $this->user->setDegreePost($values->degreePost);
//        $this->user->setEmail($values->email);
//        $this->user->setPhone($values->phone);
//        $this->user->setBirthdate($values->birthdate);
//        $this->user->setStreet($values->street);
//        $this->user->setCity($values->city);
//        $this->user->setPostcode($values->postcode);
//
//        $photo = $values->photo;
//        if ($photo->getError() === UPLOAD_ERR_OK) {
//            $photoExtension = image_type_to_extension(getimagesizefromstring($photo->getContents())[2]);
//            $photoName      = 'ext_' . $this->user->getId() . $photoExtension;
//
//            $path = $this->filesService->save($photo, User::PHOTO_PATH, false, $photoName);
//            $this->filesService->resizeAndCropImage($path, 135, 180);
//
//            $this->user->setPhoto($path);
//        }
//
//        $this->userRepository->save($this->user);
    }
}
