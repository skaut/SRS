<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 17.2.13
 * Time: 11:30
 * To change this template use File | Settings | File Templates.
 */
namespace FrontModule;

class ProfilePresenter extends BasePresenter
{
    protected $userRepo;

    protected $skautIS;

    public function startup()
    {
        parent::startup();

        $this->userRepo = $this->context->database->getRepository('\SRS\Model\User');
        $this->skautIS = $this->context->skautIS;

        if (!$this->context->user->isLoggedIn()) {
            $this->flashMessage('Pro přístup do profilu musíte být přihlášeni', 'error');
            $this->redirect(':Front:Page');
        }
    }


    public function renderDefault() {

        $form = $this['profileForm'];
        $form->bindEntity($this->userRepo->find($this->context->user->id));

    }

    protected function createComponentProfileForm()
    {
        return new \SRS\Form\ProfileForm();
    }

}
