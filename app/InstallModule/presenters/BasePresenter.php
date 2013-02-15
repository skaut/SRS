<?php

namespace InstallModule;
/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends \SRS\BasePresenter
{
    public function startup() {

        //jak spustit console prikaz
//        $arguments = array(
//
//        );
//
//       $input = new \Symfony\Component\Console\Input\ArrayInput($arguments);
//       $output = new \Symfony\Component\Console\Output\NullOutput();
//       $command = $this->context->RoleInitialDataCommand->run($input, $output);

        parent::startup();
        if (!$this->context->user->isLoggedIn()) {
            $this->redirect(":Auth:login", array('backlink' => $this->backlink()));
        }

//        if ($this->context->user->isInRole('guest')) {
//            $this->flashMessage('Pro vstup do administrace nemáte dostatečné oprávnění');
//            $this->redirect(':Homepage:default');
//        }
    }
    

}
