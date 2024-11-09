<?php

declare(strict_types=1);

namespace App\AdminModule\Forms;

use App\AdminModule\Presenters\AdminBasePresenter;
use App\Model\User\Repositories\UserRepository;
use App\Model\User\User;
use App\Services\ApplicationService;
use Contributte\Translation\Translator;
use Nette;
use Nette\Application\UI\Form;
use Nette\Utils\ImageException;
use stdClass;

use function assert;

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
            ->addRule(Form::NOT_EQUAL, 'admin.users.users_target_user_same', $this->user->getId())
            ->setHtmlAttribute('data-live-search', 'true');

        $form->addSubmit('submit', 'admin.users.users_transfer')
            ->setDisabled(! $this->user->isRegistered())
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
        $presenter = $form->getPresenter();
        assert($presenter instanceof AdminBasePresenter);

        $loggedUser = $presenter->getDbUser();

        $targetUser = $this->userRepository->findById($values->targetUser);

        $this->applicationService->transferRegistration($this->user, $targetUser, $loggedUser);
    }
}
