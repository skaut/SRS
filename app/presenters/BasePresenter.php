<?php

namespace SRS;
/**
 * Base presenter pro celou SRS aplikaci, primo nebo neprimo jej dedi kazdy presenter v projektu
 */
abstract class BasePresenter extends BaseComponentsPresenter
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

    public function beforeRender() {
        parent::beforeRender();
        if ($this->isAjax()) {
            //aby fungovali flashmessages pri ajaxu
            $this->invalidateControl('flashMessages');
        }
    }

    public function startup()
    {
        parent::startup();
        //nastaveni ACL
        $acl = new \SRS\Security\Acl($this->context->database);
        $this->context->user->setAuthorizator($acl);
       // \Nette\Diagnostics\Debugger::dump($acl->isAllowed('Administrátor', 'Administrace', 'Přístup'));

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
        \Nette\Diagnostics\Debugger::barDump(\Nette\Diagnostics\Debugger::timer(), 'platnost skautis tokenu konec');
    }



}
