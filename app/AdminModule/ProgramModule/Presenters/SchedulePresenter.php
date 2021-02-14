<?php

declare(strict_types=1);

namespace App\AdminModule\ProgramModule\Presenters;

use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Settings\Queries\SettingBoolValueQuery;
use App\Model\Settings\Settings;
use Throwable;

/**
 * Presenter obsluhující správu harmonogramu.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SchedulePresenter extends ProgramBasePresenter
{
    /**
     * @throws SettingsItemNotFoundException
     * @throws Throwable
     */
    public function renderDefault(): void
    {
        $this->template->isAllowedModifySchedule = $this->queryBus->handle(new SettingBoolValueQuery(Settings::IS_ALLOWED_MODIFY_SCHEDULE));
    }
}
