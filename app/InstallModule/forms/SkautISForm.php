<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 1.12.12
 * Time: 18:58
 * To change this template use File | Settings | File Templates.
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
        $this->addText('skautis_seminar_id', 'SkautIS ID semináře:');
        $this->addSubmit('submit','Pokračovat')->getControlPrototype()->class('btn');
        $this->onSuccess[] = callback($this, 'formSubmitted');
    }

    public function formSubmitted()
    {
        $values = $this->getValues();
        //$this->presenter->dbsettings->set('skautis_app_id', $values['skaut_is_app_id']);

        $config = \Nette\Utils\Neon::decode(file_get_contents(APP_DIR.'/config/config.neon'));
        $isDebug = $config['common']['parameters']['debug'];
        $environment = $isDebug == true ? 'development': 'production';
        $config["{$environment} < common"]['parameters']['skautis']['app_id'] = $values['skautis_app_id'];
        $configFile = \Nette\Utils\Neon::encode($config, \Nette\Utils\Neon::BLOCK);
        $result = \file_put_contents(APP_DIR.'/config/config.neon', $configFile);
        //TODO seminar id
        if ($result === false) {
            $this->presenter->flashMessage('Nepodařilo se informace zapsat do souboru config.neon. Zkontrolujte práva k souboru');

        }
        else {

            $this->presenter->redirect(':Install:install:admin');
        }

    }

}