<?php

declare(strict_types=1);

namespace App\ActionModule\Presenters;

use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\Enums\ApplicationState;
use App\Model\Mailing\Template;
use App\Model\Mailing\TemplateVariable;
use App\Model\Program\ProgramRepository;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Model\Settings\SettingsRepository;
use App\Model\User\Application;
use App\Model\User\ApplicationRepository;
use App\Model\User\RolesApplicationRepository;
use App\Model\User\SubeventsApplicationRepository;
use App\Model\User\UserRepository;
use App\Services\ApplicationService;
use App\Services\MailService;
use App\Services\PaymentService;
use App\Services\ProgramService;
use App\Utils\Helpers;

/**
 * Presenter obsluhující načítání plateb z API banky.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class PaymentPresenter extends ActionBasePresenter
{
    /**
     * @var PaymentService
     * @inject
     */
    public $paymentService;


    /**
     * Zkontroluje splatnost přihlášek.
     * @throws SettingsException
     * @throws \Throwable
     */
    public function actionCheck() : void
    {
        $this->paymentService->readFromFio();
    }
}
