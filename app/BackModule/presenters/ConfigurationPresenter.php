<?php

namespace BackModule;


/**
 * presenter obsluhujici balicek konfigurace
 */
class ConfigurationPresenter extends BasePresenter
{

    public function startup()
    {
        parent::startup();

    }

    public function renderDefault()
    {
        $this->template->configParameters = $this->context->parameters;
    }

    public function renderSkautIS()
    {
        $skautISSeminarID = $this->dbsettings->get('skautis_seminar_id');
        $skautISSeminarName = $this->dbsettings->get('skautis_seminar_name');

        $this->template->seminarID = $skautISSeminarID;
        $this->template->seminarName = $skautISSeminarName;

    }

    public function handleDisconnectEvent()
    {
        $this->dbsettings->set('skautis_seminar_id', '');
        $this->dbsettings->set('skautis_seminar_name', '');
        $this->flashMessage('Systém odpojen od skautIS akce', 'success');
        $this->redirect('this');
    }

    public function handleSyncParticipants()
    {
        $usersToSync = $this->context->database->getRepository('\SRS\Model\User')->findAllForSkautISSync();
        try {
            $count = $this->context->skautIS->syncParticipants($this->user->identity->token, $this->dbsettings->get('skautis_seminar_id'), $usersToSync);
            $this->flashMessage("Do skautIS bylo vloženo {$count} účastníků");
        } catch (\SoapFault $e) {
            $this->flashMessage('Synchronizace se nezdařila. Je pravděpodobné, že pro provedení synchronizace nemáte patřičná práva. Požádejte o synchronizaci uživatele, který akci propojil se skautIS', 'error forever');
        }

        $this->redirect('this');
    }

    public function handleClearCache()
    {
        $options = array('command' => 'srs:cc');
        $output = new \Symfony\Component\Console\Output\NullOutput();
        $input = new \Symfony\Component\Console\Input\ArrayInput($options);
        $this->context->console->application->setAutoExit(false);

        $this->context->console->application->run($input, $output);
        $this->flashMessage('Cache promazána');
        $this->redirect('this');
    }

    public function handleRefreshDBSettings()
    {
        $options = array('command' => 'srs:initial-data:settings');
        $output = new \Symfony\Component\Console\Output\NullOutput();
        $input = new \Symfony\Component\Console\Input\ArrayInput($options);
        $this->context->console->application->setAutoExit(false);

        $this->context->console->application->run($input, $output);
        $this->flashMessage('Databázové nastavení aktualizováno');
        $this->redirect('this');
    }

    protected function createComponentSettingsFormCustom()
    {
        return new \SRS\Form\Configuration\SettingsFormCustom(null, null, $this->dbsettings, $this->context->parameters);
    }

    protected function createComponentSettingsFormPayment()
    {
        return new \SRS\Form\Configuration\SettingsFormPayment(null, null, $this->dbsettings);
    }

    protected function createComponentSettingsFormPrint()
    {
        return new \SRS\Form\Configuration\SettingsFormPrint(null, null, $this->dbsettings);
    }

    protected function createComponentSettingsFormProgram()
    {
        return new \SRS\Form\Configuration\SettingsFormProgram(null, null, $this->dbsettings);
    }

    protected function createComponentSettingsFormSeminar()
    {
        return new \SRS\Form\Configuration\SettingsFormSeminar(null, null, $this->dbsettings);
    }

    protected function createComponentSettingsFormSystem()
    {
        return new \SRS\Form\Configuration\SettingsFormSystem(null, null, $this->dbsettings);
    }

    protected function createComponentSkautISEventForm()
    {
        try {
            $events = $this->context->skautIS->getEvents($this->user->identity->token);
        } catch (\SoapFault $e) {
            $events = array();
        }

        return new \SRS\Form\Configuration\SkautISEventForm(null, null, $events);
    }


}
