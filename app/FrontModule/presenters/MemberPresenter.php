<?php
namespace FrontModule;

/**
 * Zobrazuje instrukce pro propojení
 */
class MemberPresenter extends BasePresenter
{
    protected $userRepo;

    protected $skautIS;

    public function startup()
    {
        parent::startup();

        $this->userRepo = $this->context->database->getRepository('\SRS\Model\User');
        $this->skautIS = $this->context->skautIS;

        if (!$this->context->user->isLoggedIn()) {
            $this->flashMessage('Pro přístup musíte být přihlášeni', 'error');
            $this->redirect(':Front:Page:default');
        }
    }

    public function renderDefault()
    {
        /**
         * @var \SRS\Model\User
         */
        $this->template->dbuser = $this->userRepo->find($this->context->user->id);
    }
}
