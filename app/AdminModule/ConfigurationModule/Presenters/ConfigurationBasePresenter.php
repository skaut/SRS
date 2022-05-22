<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Presenters;

use App\AdminModule\Presenters\AdminBasePresenter;
use App\Model\Acl\Permission;
use App\Model\Acl\SrsResource;
use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Structure\Repositories\SubeventRepository;
use Doctrine\ORM\NonUniqueResultException;
use Nette\Application\AbortException;
use Nette\DI\Attributes\Inject;
use Throwable;

/**
 * Basepresenter pro ConfigurationModule.
 */
abstract class ConfigurationBasePresenter extends AdminBasePresenter
{
    protected string $resource = SrsResource::CONFIGURATION;

    #[Inject]
    public SubeventRepository $subeventRepository;

    /**
     * @throws AbortException
     */
    public function startup(): void
    {
        parent::startup();

        $this->checkPermission(Permission::MANAGE);
    }

    /**
     * @throws SettingsItemNotFoundException
     * @throws NonUniqueResultException
     * @throws Throwable
     */
    public function beforeRender(): void
    {
        parent::beforeRender();

        $this->template->explicitSubeventsExists = $this->subeventRepository->explicitSubeventsExists();
    }
}
