<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 8.5.13
 * Time: 18:15
 * To change this template use File | Settings | File Templates.
 */
namespace SRS\Form\Mailing;
use \Nette\Application\UI\Form;
use SRS\Model\Acl\Role;

class MailingForm extends \Nette\Application\UI\Form
{
    protected $rolesRepo;
    protected $dbsettings;

    public function __construct(IContainer $parent = NULL, $name = NULL, $rolesRepo, $dbsettings)
    {
        $this->rolesRepo = $rolesRepo;
        $this->dbsettings = $dbsettings;
        $roles = $rolesRepo->findAll();
        parent::__construct($parent, $name);
        $this->addGroup('Mail:');
        $this->addText('subject', 'Předmět')
            ->addRule(Form::FILLED, 'Zadejte předmět');
        $this->addTextArea('body', 'Obsah emailu')->controlPrototype->class('tinyMCE')
            ->addRule(Form::FILLED, 'Zadejte obsah');
        $this->addText('copy', 'Zaslat kontrolní skrytou kopii na:')
            ->addCondition(Form::FILLED)
            ->addRule(Form::EMAIL, 'Zadejte platný mail');
        $this->addGroup('Zaslat rolím:');
        $rolesContainer = $this->addContainer('roles');


        $i = 0;
        foreach ($roles as $role) {
            if ($role->name != Role::GUEST && $role->name != Role::UNAPPROVED) {
                $i++;
                $rolesContainer->addCheckbox("{$role->id}", "{$role->name} ({$role->users->count()} uživatelů)");
            }
        }
        $this->addGroup('');
        $this->addSubmit('submit', 'Odeslat mail')->getControlPrototype()->class('btn btn-large');

        $this->onSuccess[] = callback($this, 'formSubmitted');
        $this->getElementPrototype()->onsubmit('tinyMCE.triggerSave()');
    }


    public function formSubmitted()
    {
        $values = $this->getValues();
        $mail = new \Nette\Mail\Message();
        $mail->from = $this->dbsettings->get('seminar_email');
        $mail->subject = $values['subject'];
        $mail->setHtmlBody($values['body']);
        foreach ($values['roles'] as $role_id => $checked) {
            if ($checked) {
                $role = $this->rolesRepo->find($role_id);
                foreach ($role->users as $user) {
                    $mail->addBcc($user->email);
                }
            }
        }
        if ($values['copy'])
            $mail->addBcc($values['copy']);

        $mail->send();
        $this->presenter->flashMessage('Email úspěšně odeslán', 'success');
        $this->presenter->redirect('this');
    }

}
