<?php

namespace App\Model\Settings;

use Doctrine\ORM\Mapping as ORM;


/**
 * Entita nastavení.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity(repositoryClass="SettingsRepository")
 * @ORM\Table(name="settings")
 */
class Settings
{
    /**
     * Administrátor byl vytvořen.
     */
    const ADMIN_CREATED = 'admin_created';

    /**
     * Název semináře.
     */
    const SEMINAR_NAME = 'seminar_name';

    /**
     * E-mail semináře.
     */
    const SEMINAR_EMAIL = 'seminar_email';

    /**
     * Začátek semináře.
     */
    const SEMINAR_FROM_DATE = 'seminar_from_date';

    /**
     * Konec semináře.
     */
    const SEMINAR_TO_DATE = 'seminar_to_date';

    /**
     * Povoleno přidávat programové bloky.
     */
    const IS_ALLOWED_ADD_BLOCK = 'is_allowed_add_block';

    /**
     * Povoleno upravovat harmonogram.
     */
    const IS_ALLOWED_MODIFY_SCHEDULE = 'is_allowed_modify_schedule';

    /**
     * Povoleno přihlašovat se na programy.
     */
    const IS_ALLOWED_REGISTER_PROGRAMS = 'is_allowed_register_programs';

    /**
     * Povoleno přihlašovat se na programy před zaplacením.
     */
    const IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT = 'is_allowed_register_programs_before_payment';

    /**
     * Id propojené skautIS akce.
     */
    const SKAUTIS_EVENT_ID = 'skautis_event_id';

    /**
     * Název propojené skautIS akce.
     */
    const SKAUTIS_EVENT_NAME = 'skautis_event_name';

    /**
     * Adresa obrázku s logem.
     */
    const LOGO = 'logo';

    /**
     * Text patičky.
     */
    const FOOTER = 'footer';

    /**
     * Dodavatel.
     */
    const COMPANY = 'company';

    /**
     * IČO.
     */
    const ICO = 'ico';

    /**
     * Jméno pokladníka.
     */
    const ACCOUNTANT = 'accountant';

    /**
     * Místo vystavení dokladu.
     */
    const PRINT_LOCATION = 'print_location';

    /**
     * Číslo účtu.
     */
    const ACCOUNT_NUMBER = 'account_number';

    /**
     * Předvolba variabilního symbolu. 2 číslice před generovaným variabilním symbolem.
     */
    const VARIABLE_SYMBOL_CODE = 'variable_symbol_code';

    /**
     * Přihlašování na programy od.
     */
    const REGISTER_PROGRAMS_FROM = 'register_programs_from';

    /**
     * Přihlašování na programy do.
     */
    const REGISTER_PROGRAMS_TO = 'register_programs_to';

    /**
     * Odhlášení ze semináře a změna rolí povolena do.
     */
    const EDIT_REGISTRATION_TO = 'edit_registration_to';

    /**
     * Zobrazovat role přihlášeného uživatele.
     */
    const DISPLAY_USERS_ROLES = 'display_users_roles';

    /**
     * Stránka, na kterou budou přesměrováni uživatelé po přihlášení, pokud není jinak specifikováno u jejich role.
     */
    const REDIRECT_AFTER_LOGIN = 'redirect_after_login';

    /**
     * Popis místa a cesty.
     */
    const PLACE_DESCRIPTION = 'place_description';


    /**
     * Název položky nastavení.
     * @ORM\Column(type="string", unique=true)
     * @ORM\Id
     * @var string
     */
    protected $item;

    /**
     * Hodnota položky nastavení.
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

