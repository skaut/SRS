<?php

namespace SRS;
/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends \Nette\Application\UI\Presenter
{
    /** @var Doctrine\ORM\EntityManager */
    protected $em;

    public function injectEntityManager(\Doctrine\ORM\EntityManager $em)
    {
        if ($this->em) {
            throw new \Nette\InvalidStateException('Entity manager has already been set');
        }
        $this->em = $em;
        return $this;
    }

    public function startup() {
        parent::startup();

        //@TODO
        $acl = new \Nette\Security\Permission();
        $acl->addRole('guest');
        $acl->addRole('registered');
        $acl->addRole('service', array('guest', 'registered'));
        $this->context->user->setAuthorizator($acl);

        //Při každém načtení stránky prodlužujeme platnost skautIS Tokenu
        if ($this->user->isLoggedIn()) {
            try {
            $this->context->skautIS->refreshUserExpiration($this->user->getIdentity()->token);
            }
            catch (\SoapFault $e) {
                \Nette\Diagnostics\Debugger::log('Nepodařilo se prodloužit platnost skautISTokenu, uživatel '.$this->user->getId().', message: '.$e->getMessage());
                $this->context->user->logout(true);
                $this->redirect(':Auth:login');
            }
        }

    }
    

}
