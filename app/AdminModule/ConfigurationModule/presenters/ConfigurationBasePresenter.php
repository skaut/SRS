<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Presenters;

use App\AdminModule\Presenters\AdminBasePresenter;
use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Model\Settings\SettingsException;
use App\Model\Structure\SubeventRepository;
use Doctrine\ORM\NonUniqueResultException;
use Nette\Application\AbortException;

/**
 * Basepresenter pro ConfigurationModule.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
abstract class ConfigurationBasePresenter extends AdminBasePresenter
{
    /** @var string */
    protected $resource = Resource::CONFIGURATION;

    /**
     * @var    SubeventRepository
     * @inject
     */
    public $subeventRepository;

    /**
     * @throws AbortException
     */
    public function startup() : void
    {
        parent::startup();

        $this->checkPermission(Permission::MANAGE);
    }

    /**
     * @throws SettingsException
     * @throws NonUniqueResultException
     * @throws \Throwable
     */
    public function beforeRender() : void
    {
        parent::beforeRender();

        $this->template->sidebarVisible          = true;
        $this->template->explicitSubeventsExists = $this->subeventRepository->explicitSubeventsExists();
    }
}
