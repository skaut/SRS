<?php

namespace BackModule;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends \SRS\BasePresenter
{
    protected $dbuser;

    function templatePrepareFilters($t)
    {
        $t->registerFilter($l = new \Nette\Latte\Engine);
        $l = new \Nette\Latte\Macros\MacroSet($l->compiler); // in 12.1. $l->parser  --->   $l->compile
        $l->addMacro('bool', array($this, 'booleanMacro'));

    }

    public function startup() {
        parent::startup();
        if (!$this->context->user->isLoggedIn()) {
            $this->redirect(":Auth:login", array('backlink' => $this->backlink()));
        }

        if (!$this->isAjax()) { //kvuli ajaxovym pozadavkym na program
            if (!$this->context->user->isAllowed('Administrace', 'Přístup' )) {
                $this->flashMessage('Pro vstup do administrace nemáte dostatečné oprávnění');
                $this->redirect(':Front:Page:Default');
            }
        }

       $this->dbuser = $this->context->database->getRepository('\SRS\Model\User')->find($this->context->user->id);

    }

    protected function checkPermissions($permission) {
        if (!$this->context->user->isAllowed($this->resource, $permission )) {
            $this->flashMessage('Nemáte dostatečné oprávnění');
            $this->redirect(':Back:Dashboard:Default');
        }
    }

    public function booleanMacro(\Nette\Latte\MacroNode $node, \Nette\Latte\PhpWriter $writer) {
        $args = ($node->tokenizer->fetchAll());
        $array_args = explode(" ", $args);
        return $writer->write('echo \SRS\Helpers::renderBoolean('.$array_args[0].')');
    }
    

}
