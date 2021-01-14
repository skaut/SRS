<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Presenters;

use App\Commands\ClearCacheCommand;
use Contributte\Console\Application;
use Exception;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Presenter obsluhující nastavení systému.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class SystemPresenter extends ConfigurationBasePresenter
{
    /** @inject */
    public ClearCacheCommand $clearCacheCommand;

    /** @inject */
    public Application $consoleApplication;

    /**
     * Promaže cache.
     *
     * @throws Exception
     */
    public function handleClearCache(): void
    {
        $output = new BufferedOutput();
        $input  = new ArrayInput(['command' => 'app:cache:clear']);
        $this->consoleApplication->add($this->clearCacheCommand);
        $this->consoleApplication->setAutoExit(false);
        $result = $this->consoleApplication->run($input, $output);

        if ($result === 0) {
            $this->flashMessage('admin.configuration.system_cache_cleared', 'success');
        } else {
            $this->flashMessage('admin.configuration.system_cache_not_cleared', 'error');
        }

        $this->redirect('this');
    }
}
