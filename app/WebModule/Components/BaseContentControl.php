<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Acl\Role;
use App\Model\Cms\Dto\ContentDto;
use App\Model\CustomInput\Repositories\CustomInputRepository;
use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Settings;
use App\Model\Structure\Repositories\SubeventRepository;
use App\Model\User\Repositories\UserRepository;
use App\Services\Authenticator;
use App\Services\QueryBus;
use App\WebModule\Forms\ApplicationFormFactory;
use Doctrine\ORM\NonUniqueResultException;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use stdClass;
use Throwable;

class BaseContentControl extends Control
{
    public function renderScripts() { }
}
