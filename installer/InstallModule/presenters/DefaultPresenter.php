<?php

declare(strict_types=1);

namespace App\InstallModule\Presenters;

use App\InstallModule\Forms\ConfigParametersForm;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;
use WebLoader\Nette\CssLoader;
use WebLoader\Nette\JavaScriptLoader;
use WebLoader\Nette\LoaderFactory;

/**
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class DefaultPresenter extends Presenter
{
    
    /**
     * @var ConfigParametersForm
     * @inject
     */
    public $configParametersForm;

    /**
     * @var Translator
     * @inject
     */
    public $translator;

    /**
     * @var LoaderFactory
     * @inject
     */
    public $webLoader;

    protected function createComponentConfigParametersForm() : Form
    {
        $form = $this->configParametersForm->create();
        $form->onSuccess[] = function (Form $form, \stdClass $values) : void {
            $this->redirectUrl('/');
        };

        return $form;
    }
    
    /**
     * Načte css podle konfigurace v config.neon.
     */
    protected function createComponentCss() : CssLoader
    {
        return $this->webLoader->createCssLoader('install');
    }

    /**
     * Načte javascript podle konfigurace v config.neon.
     */
    protected function createComponentJs() : JavaScriptLoader
    {
        return $this->webLoader->createJavaScriptLoader('install');
    }
}
