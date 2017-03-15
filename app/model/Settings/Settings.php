<?php

namespace App\Model\Settings;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="SettingsRepository")
 * @ORM\Table(name="settings")
 */
class Settings
{
    const ADMIN_CREATED = 'admin_created';

    const SEMINAR_NAME = 'seminar_name';
    const SEMINAR_EMAIL = 'seminar_email';
    const SEMINAR_FROM_DATE = 'seminar_from_date';
    const SEMINAR_TO_DATE = 'seminar_to_date';

    const IS_ALLOWED_ADD_BLOCK = 'is_allowed_add_block';
    const IS_ALLOWED_MODIFY_SCHEDULE = 'is_allowed_modify_schedule';
    const IS_ALLOWED_REGISTER_PROGRAMS = 'is_allowed_register_programs';
    const IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT = 'is_allowed_register_programs_before_payment';

    const SKAUTIS_EVENT_ID = 'skautis_event_id';
    const SKAUTIS_EVENT_NAME = 'skautis_event_name';

    const LOGO = 'logo';
    const FOOTER = 'footer';

    const COMPANY = 'company';
    const ICO = 'ico';
    const ACCOUNTANT = 'accountant';
    const PRINT_LOCATION = 'print_location';
    const ACCOUNT_NUMBER = 'account_number';
    const VARIABLE_SYMBOL_CODE = 'variable_symbol_code';

    const REGISTER_PROGRAMS_FROM = 'register_programs_from';
    const REGISTER_PROGRAMS_TO = 'register_programs_to';
    const EDIT_REGISTRATION_TO = 'edit_registration_to';

    const DISPLAY_USERS_ROLES = 'display_users_roles';
    const REDIRECT_AFTER_LOGIN = 'redirect_after_login';

    const PLACE_DESCRIPTION = 'place_description';


    /**
     * @ORM\Column(type="string", unique=true)
     * @ORM\Id
     * @var string
     */
    protected $item;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    protected $value;

    /**
     * Settings constructor.
     * @param string $item
     * @param string $value
     */
    public function __construct($item, $value)
    {
        $this->item = $item;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getItem()
    {
        return $this->item;
    }

    /**
     * @param string $item
     */
    public function setItem($item)
    {
        $this->item = $item;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }
}

