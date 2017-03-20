<?php

namespace App\AdminModule\ConfigurationModule\Presenters;

use App\Commands\ClearCacheCommand;
use Kdyby\Console\StringOutput;
use Symfony\Component\Console\Input\ArrayInput;


class SystemPresenter extends ConfigurationBasePresenter
{
    /**
     * @var \Kdyby\Console\Application
     * @inject
     */
    public $application;

    /**
     * @var ClearCacheCommand
     * @inject
     */
    public $clearCacheCommand;


    public function handleClearCache()
    {
        $this->application->add($this->clearCacheCommand);
        $output = new StringOutput();
        $input = new ArrayInput([
            'command' => 'app:cache:clear'
        ]);
        $result = $this->application->run($input, $output);

        if ($result == 0)
            $this->flashMessage('admin.configuration.system_cache_cleared', 'success');
        else
            $this->flashMessage('admin.configuration.system_cache_not_cleared', 'error');

        $this->redirect('this');
    }
}