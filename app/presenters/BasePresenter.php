<?php

namespace SRS;
use Kdyby\Translation\TemplateHelpers;

/**
 * Base presenter pro celou SRS aplikaci, primo nebo neprimo jej dedi kazdy presenter v projektu
 */
abstract class BasePresenter extends BaseComponentsPresenter
{
    /** @var \Kdyby\Translation\Translator */
    public $translator;

    /** @var \Doctrine\ORM\EntityManager */
    protected $em;

    /**
     * @var \SRS\Model\SettingsRepository
     */
    public $dbsettings;

    public function injectEntityManager(\Doctrine\ORM\EntityManager $em)
    {
        if ($this->em) {
            throw new \Nette\InvalidStateException('Entity manager has already been set');
        }
        $this->em = $em;
        return $this;
    }

    public function injectTranslator(\Kdyby\Translation\Translator $translator)
    {
        $this->translator = $translator;
    }

    public function startup()
    {
        parent::startup();

        $this->dbsettings = $this->presenter->context->database->getRepository('\SRS\Model\Settings');

        //nastaveni ACL
        $acl = new \SRS\Security\Acl($this->context->database);
        $this->context->user->setAuthorizator($acl);

        //Při každém načtení stránky prodlužujeme platnost skautIS Tokenu
        if ($this->user->isLoggedIn()) {
            try {
                $this->context->skautIS->refreshUserExpiration($this->user->getIdentity()->token);
            } catch (\SoapFault $e) {
                \Nette\Diagnostics\Debugger::log('Nepodařilo se prodloužit platnost skautISTokenu, uživatel ' . $this->user->getId() . ', message: ' . $e->getMessage());
                $this->context->user->logout(true);
                $this->redirect(':Auth:login');
            }
        }

    }

    public function beforeRender()
    {
        parent::beforeRender();
        if ($this->isAjax()) {
            //aby fungovali flashmessages pri ajaxu
            $this->invalidateControl('flashMessages');
        }
    }
}
