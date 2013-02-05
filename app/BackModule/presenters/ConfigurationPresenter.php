<?php

namespace BackModule;
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 5.2.13
 * Time: 16:36
 * To change this template use File | Settings | File Templates.
 */
class ConfigurationPresenter extends BasePresenter
{

    public function startup() {
        parent::startup();

    }

    public function renderDefault() {

    }

    public function handleClearCache() {
        $options = array('command' => 'srs:cc');
        $output = new \Symfony\Component\Console\Output\NullOutput();
        $input = new \Symfony\Component\Console\Input\ArrayInput($options);
        $this->context->console->application->setAutoExit(false);

        $this->context->console->application->run($input, $output);
        $this->flashMessage('Cache promazána');
        $this->redirect('this');
    }


    protected function createComponentSettingsForm() {
        return new \SRS\Form\SettingsForm(null, null, $this->dbsettings);
    }

}
