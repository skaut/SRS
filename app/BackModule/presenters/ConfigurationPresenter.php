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
//        SELECT u.*, c.food, c2.medic FROM user u left join (SELECT c.user_id , c.val as food FROM custom c WHERE c.key="food") as c on c.user_id = u.id
//        left join (SELECT c2.user_id , c2.val as medic FROM custom c2 WHERE c2.key="medic") as c2 on c2.user_id = u.id



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

//    public function handleAddColumn() {
//        $sm = $this->context->database->getConnection()->getSchemaManager();
//        $table = $sm->listTableDetails('user');
//        $column = $table->addColumn('custom_name_'.mt_rand(0,100), 'boolean');
//        $diff = new \Doctrine\DBAL\Schema\TableDiff('user', array($column));
//        $sm->alterTable($diff);
//
//
//    }


    protected function createComponentSettingsForm() {
        return new \SRS\Form\SettingsForm(null, null, $this->dbsettings, $this->context->parameters);
    }

}
