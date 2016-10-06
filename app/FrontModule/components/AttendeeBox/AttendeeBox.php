<?php
/**
 * Date: 19.1.13
 * Time: 10:37
 * Author: Michal Májský
 */
namespace SRS\Components;
use \SRS\Model\Acl\Role;

/**
 *  Komponenta obsluhujici prihlasovaci formular na FE
 */
class AttendeeBox extends \Nette\Application\UI\Control
{

    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/template.latte');

        $user = $this->presenter->context->user;
        if ($user->isLoggedIn()) {
            $dbuser = $this->presenter->context->database->getRepository('\SRS\Model\User')->find($this->presenter->context->user->id);
            $template->dbuser = $dbuser;

            if ($dbuser->isInRole(Role::REGISTERED)) {
                $form = $this['attendeeForm'];

                $roles = $this->presenter->context->database->getRepository('\SRS\Model\Acl\Role')->findRegisterableNow();
                $roleFormChoices = array();
                foreach ($roles as $role) {
                    $vacancies = $role->countVacancies();
                    if ($vacancies !== null)
                        $roleFormChoices[$role->id] = "{$role->name} (volno {$vacancies}/{$role->usersLimit})";
                    else
                        $roleFormChoices[$role->id] = "{$role->name}";
                }
                $this['attendeeForm']['roles']->setItems($roleFormChoices);

                $form->bindEntity($user->identity->object);
            }
        }
        //$template->user = $this->presenter->context->user;
        $template->backlink = $this->presenter->context->httpRequest->url->path;
        $template->render();
    }

    public function createComponentAttendeeForm()
    {
        $roles = $this->presenter->context->database->getRepository('\SRS\Model\Acl\Role')->findRegisterableNow();
        return new \SRS\Form\AttendeeForm(null, null, \SRS\Form\EntityForm::getFormChoices($roles), $this->presenter->context->parameters, $this->presenter->dbsettings, $this->presenter->context->database);
    }

}