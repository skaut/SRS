<?php

namespace SRS;

/**
 * Obsahuje komponenty a vlastni latte filtry, ktere jsou vyuzivany ve vice castech aplikace
 */
abstract class BaseComponentsPresenter extends \Nette\Application\UI\Presenter
{

    function templatePrepareFilters($t)
    {
        $t->registerFilter($l = new \Nette\Latte\Engine);
        $l = new \Nette\Latte\Macros\MacroSet($l->compiler); // in 12.1. $l->parser  --->   $l->compile
        $l->addMacro('bool', array($this, 'booleanMacro'));

    }

    public function booleanMacro(\Nette\Latte\MacroNode $node, \Nette\Latte\PhpWriter $writer)
    {
        $args = ($node->tokenizer->fetchAll());
        $array_args = explode(" ", $args);
        return $writer->write('echo \SRS\Helpers::renderBoolean(' . $array_args[0] . ')');
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
        $filter = new \WebLoader\Filter\CssUrlsFilter(WWW_DIR, $this->template->basePath);
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

    public function getDBParams()
    {
        $config = \Nette\Utils\Neon::decode(file_get_contents(APP_DIR . '/config/config.neon'));
        $isDebug = $config['common']['parameters']['debug'];
        $environment = $isDebug == true ? 'development' : 'production';
        return $config["{$environment} < common"]['parameters']['database'];
    }


}
