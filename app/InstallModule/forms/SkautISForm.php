<?php
/**
 * Date: 1.12.12
 * Time: 18:58
 * Author: Michal Májský
 */


namespace SRS\Form\Install;

use Nette\Application\UI,
    Nette\Diagnostics\Debugger,
    Nette\Application\UI\Form,
    Nette\ComponentModel\IContainer;


class SkautISForm extends UI\Form
{
    public function __construct(IContainer $parent = NULL, $name = NULL)
    {
        parent::__construct($parent, $name);

        $this->addText('skautis_app_id', 'SkautIS app ID:')
            ->addRule(Form::FILLED, 'Zadejte skautIS App ID');
        $this->addSubmit('submit', 'Pokračovat')->getControlPrototype()->class('btn');
        $this->onSuccess[] = callback($this, 'formSubmitted');
    }

    public function formSubmitted()
    {
        $values = $this->getValues();

        $testResult = $this->presenter->context->skautIS->checkAppId($values['skautis_app_id']);
        if ($testResult['success'] === true) {
            $config = \Nette\Utils\Neon::decode(file_get_contents(APP_DIR . '/config/config.neon'));
            $isDebug = $config['common']['parameters']['debug'];
            $environment = $isDebug == true ? 'development' : 'production';
            $config["{$environment} < common"]['parameters']['skautis']['app_id'] = $values['skautis_app_id'];
            $configFile = \Nette\Utils\Neon::encode($config, \Nette\Utils\Neon::BLOCK);
            $result = \file_put_contents(APP_DIR . '/config/config.neon', $configFile);

            if ($result === false) {
                $this->presenter->flashMessage('Nepodařilo se informace zapsat do souboru config.neon. Zkontrolujte práva k souboru');

            } else {
                $this->presenter->flashMessage('Oveření skautIS App ID proběhlo úspěšně.');
                $this->presenter->redirect(':Install:install:admin');
            }
        } else {
            $this->presenter->flashMessage("Nepodařilo se ověřit skautIS App ID. Ujistěte se, že zadáváte správné údaje.");
        }

    }

}