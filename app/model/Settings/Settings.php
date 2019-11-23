<?php

declare(strict_types=1);

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
    public const ADMIN_CREATED = 'admin_created';

    /**
     * Název semináře.
     */
    public const SEMINAR_NAME = 'seminar_name';

    /**
     * E-mail semináře.
     */
    public const SEMINAR_EMAIL = 'seminar_email';

    /**
     * Neověřený změněný e-mail semináře.
     */
    public const SEMINAR_EMAIL_UNVERIFIED = 'seminar_email_unverified';

    /**
     * Ověřovací kód pro změnu e-mailu.
     */
    public const SEMINAR_EMAIL_VERIFICATION_CODE = 'seminar_email_verification_code';

    /**
     * Začátek semináře.
     */
    public const SEMINAR_FROM_DATE = 'seminar_from_date';

    /**
     * Konec semináře.
     */
    public const SEMINAR_TO_DATE = 'seminar_to_date';

    /**
     * Povoleno přidávat programové bloky.
     */
    public const IS_ALLOWED_ADD_BLOCK = 'is_allowed_add_block';

    /**
     * Povoleno upravovat harmonogram.
     */
    public const IS_ALLOWED_MODIFY_SCHEDULE = 'is_allowed_modify_schedule';

    /**
     * Povoleno přihlašovat se na programy před zaplacením.
     */
    public const IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT = 'is_allowed_register_programs_before_payment';

    /**
     * Povoleno přidávání podakcí po zaplacení.
     */
    public const IS_ALLOWED_ADD_SUBEVENTS_AFTER_PAYMENT = 'is_allowed_add_subevents_after_payment';

    /**
     * Id propojené skautIS akce.
     */
    public const SKAUTIS_EVENT_ID = 'skautis_event_id';

    /**
     * Typ propojené skautIS akce.
     */
    public const SKAUTIS_EVENT_TYPE = 'skautis_event_type';

    /**
     * Název propojené skautIS akce.
     */
    public const SKAUTIS_EVENT_NAME = 'skautis_event_name';

    /**
     * Adresa obrázku s logem.
     */
    public const LOGO = 'logo';

    /**
     * Text patičky.
     */
    public const FOOTER = 'footer';

    /**
     * Dodavatel.
     */
    public const COMPANY = 'company';

    /**
     * IČO.
     */
    public const ICO = 'ico';

    /**
     * Jméno pokladníka.
     */
    public const ACCOUNTANT = 'accountant';

    /**
     * Místo vystavení dokladu.
     */
    public const PRINT_LOCATION = 'print_location';

    /**
     * Číslo účtu.
     */
    public const ACCOUNT_NUMBER = 'account_number';

    /**
     * Předvolba variabilního symbolu. 0-4 číslice před generovaným variabilním symbolem.
     */
    public const VARIABLE_SYMBOL_CODE = 'variable_symbol_code';

    /**
     * Způsob povolení zápisu na programy.
     */
    public const REGISTER_PROGRAMS_TYPE = 'register_programs_type';

    /**
     * Přihlašování na programy od.
     */
    public const REGISTER_PROGRAMS_FROM = 'register_programs_from';

    /**
     * Přihlašování na programy do.
     */
    public const REGISTER_PROGRAMS_TO = 'register_programs_to';

    /**
     * Odhlášení ze semináře a změna rolí povolena do.
     */
    public const EDIT_REGISTRATION_TO = 'edit_registration_to';

    /**
     * Text souhlasu u přihlášky.
     */
    public const APPLICATION_AGREEMENT = 'application_agreement';

    /**
     * Stránka, na kterou budou přesměrováni uživatelé po přihlášení, pokud není jinak specifikováno u jejich role.
     */
    public const REDIRECT_AFTER_LOGIN = 'redirect_after_login';

    /**
     * Popis místa a cesty.
     */
    public const PLACE_DESCRIPTION = 'place_description';

    /**
     * Způsob výpočtu splatnosti.
     */
    public const MATURITY_TYPE = 'maturity_type';

    /**
     * Datum splatnosti.
     */
    public const MATURITY_DATE = 'maturity_date';

    /**
     * Počet dní pro výpočet splatnosti.
     */
    public const MATURITY_DAYS = 'maturity_days';

    /**
     * Počet pracovních dní pro výpočet splatnosti.
     */
    public const MATURITY_WORK_DAYS = 'maturity_work_days';

    /**
     * Počet dní, kdy zaslat připomenutí splatnosti.
     */
    public const MATURITY_REMINDER = 'maturity_reminder';

    /**
     * Počet dní od splatnosti, kdy zrušit nezaplacené registrace.
     */
    public const CANCEL_REGISTRATION_AFTER_MATURITY = 'cancel_registration_after_maturity';

    /**
     * Úprava vlastních polí povolena do.
     */
    public const EDIT_CUSTOM_INPUTS_TO = 'edit_custom_inputs_to';

    /**
     * Token pro přístup k API banky.
     */
    public const BANK_TOKEN = 'bank_token';

    /**
     * Počáteční datum pro stahování plateb.
     */
    public const BANK_DOWNLOAD_FROM = 'bank_download_from';

    /**
     * Datum, odkdy je možné stáhnout vstupenku.
     */
    public const TICKETS_FROM = 'tickets_from';

    /**
     * Google Analytics kód měření.
     */
    public const GA_ID = 'ga_id';


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


    public function __construct(string $item, ?string $value)
    {
        $this->item  = $item;
        $this->value = $value;
    }

    public function getValue() : ?string
    {
        return $this->value;
    }

    public function setValue(?string $value) : void
    {
        $this->value = $value;
    }
}
