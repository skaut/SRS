<?php

namespace SRS;
/**
 * Base presenter pro celou SRS aplikaci, primo nebo neprimo jej dedi kazdy presenter v projektu
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


    /**
     * Tovarnicka pro nacitani CSS souboru
     * @return \WebLoader\Nette\CssLoader
     */
    public function createComponentCssLoader()
    {
        // připravíme seznam souborů
        // FileCollection v konstruktoru může dostat výchozí adresář, pak není potřeba psát absolutní cesty
        $files = new \WebLoader\FileCollection(WWW_DIR . '/css');


        // kompilátoru seznam předáme a určíme adresář, kam má kompilovat
        $filter =  new \WebLoader\Filter\CssUrlsFilter($this->template->basePath);
        $compiler = \WebLoader\Compiler::createCssCompiler($files, WWW_DIR . '/webtemp');
        $compiler->setJoinFiles(FALSE);
        $compiler->addFileFilter($filter);


        // nette komponenta pro výpis <link>ů přijímá kompilátor a cestu k adresáři na webu
        return new \WebLoader\Nette\CssLoader($compiler, $this->template->basePath . '/webtemp');
    }

    /**
     * Tovarnicka pro nacitani JS souboru
     * @return \WebLoader\Nette\JavaScriptLoader
     */
    public function createComponentJsLoader()
    {
        $files = new \WebLoader\FileCollection(WWW_DIR . '/js');
        $compiler = \WebLoader\Compiler::createJsCompiler($files, WWW_DIR . '/webtemp');
        $compiler->setJoinFiles(FALSE);
        return new \WebLoader\Nette\JavaScriptLoader($compiler, $this->template->basePath . '/webtemp');
    }
    

}
