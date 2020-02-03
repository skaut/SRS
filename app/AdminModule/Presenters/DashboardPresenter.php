<?php

declare(strict_types=1);

namespace App\AdminModule\Presenters;

use App\Model\Settings\SettingsException;
use App\Model\Structure\SubeventRepository;
use Doctrine\ORM\NonUniqueResultException;
use Throwable;

/**
 * Presenter obsluhující úvodní stránku.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class DashboardPresenter extends AdminBasePresenter
{
    /**
     * @var SubeventRepository
     * @inject
     */
    public $subeventRepository;

    /**
     * @throws SettingsException
     * @throws NonUniqueResultException
     * @throws Throwable
     */
    public function beforeRender() : void
    {
        parent::beforeRender();

        $this->template->explicitSubeventsExists = $this->subeventRepository->explicitSubeventsExists();
    }
}
