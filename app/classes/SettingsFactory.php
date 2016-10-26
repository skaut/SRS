<?php
/**
 * Date: 15.11.12
 * Time: 13:25
 * Author: Michal Májský
 */
namespace SRS\Factory;

/**
 * Vytvari inicializacni data pro settings
 */
class SettingsFactory
{

    public static function create()
    {
        $config = \Nette\Utils\Neon::decode(file_get_contents(APP_DIR . '/config/config.neon'));
        $userCustomBooleanCount = $config['common']['parameters']['user_custom_boolean_count'];
        $userCustomTextCount = $config['common']['parameters']['user_custom_text_count'];

        $settings = array();
        $settings[] = new \SRS\Model\Settings('superadmin_created', 'Je vytvořen superadmin?', '0');
        $settings[] = new \SRS\Model\Settings('schema_imported', 'Naimportována inicializační databázová data', '1');
        $settings[] = new \SRS\Model\Settings('seminar_name', 'Jméno semináře', 'Konfigurace->Jméno semináře');
        $settings[] = new \SRS\Model\Settings('seminar_email', 'Email', 'noreply@srs.skauting.cz');
        $today = new \DateTime('now');
        $tommorow = new \DateTime('now');
        $tommorow->modify('+1 day');
        $yesterday = new \DateTime('now');
        $yesterday->modify('-1 day');
        $settings[] = new \SRS\Model\Settings('seminar_from_date', 'Začátek semináře', $today->format('d.m.Y'));
        $settings[] = new \SRS\Model\Settings('seminar_to_date', 'Konec semináře', $tommorow->format('d.m.Y'));
        $settings[] = new \SRS\Model\Settings('basic_block_duration', 'Základní délka trvání jednoho bloku semináře (minuty)', '60');
        $settings[] = new \SRS\Model\Settings('is_allowed_add_block', 'Lze vytvářet programové bloky?', '1');
        $settings[] = new \SRS\Model\Settings('is_allowed_modify_schedule', 'Lze upravovat harmonogram semináře?', '1');
        $settings[] = new \SRS\Model\Settings('is_allowed_log_in_programs', 'Lze se přihlašovat na Programy?', '0');

        // $settings[] = new \SRS\Model\Settings('skautis_app_id', 'skautis app id', '');
        $settings[] = new \SRS\Model\Settings('skautis_seminar_id', 'skautis seminar id', '');
        $settings[] = new \SRS\Model\Settings('skautis_seminar_name', 'skautis seminar name', '');

        $settings[] = new \SRS\Model\Settings('logo', 'Logo', '/img/logo.png');
        $settings[] = new \SRS\Model\Settings('footer', 'Patička', '&copy; SRS 2013');

        $settings[] = new \SRS\Model\Settings('company', 'Firma', 'Konfigurace->Firma');
        $settings[] = new \SRS\Model\Settings('ico', 'IČO', 'Konfigurace->IČO');
        $settings[] = new \SRS\Model\Settings('accountant', 'Pokladník', 'Konfigurace->Pokladník');
        $settings[] = new \SRS\Model\Settings('account_number', 'Číslo účtu', 'Konfigurace->Číslo účtu');
        $settings[] = new \SRS\Model\Settings('print_location', 'Lokalita', 'Konfigurace->Lokalita');

        $settings[] = new \SRS\Model\Settings('variable_symbol_code', 'Předvolba pro variabilní symbol', '00');

        $settings[] = new \SRS\Model\Settings('cancel_registration_to_date', 'Odhlašování povoleno do', $today->format('d.m.Y'));

        $settings[] = new \SRS\Model\Settings('log_in_programs_from', 'Přihlašování programů otevřeno od', $yesterday->format('d.m.Y H:i'));
        $settings[] = new \SRS\Model\Settings('log_in_programs_to', 'Přihlašování programů otevřeno do', $today->format('d.m.Y H:i'));

        for ($i = 0; $i < $userCustomBooleanCount; $i++) {
            $num = $i+1;
            $settings[] = new \SRS\Model\Settings('user_custom_boolean_'.$i, 'Vlastní checkbox přihlášky č.'.$num, '');
        }

        for ($i = 0; $i < $userCustomTextCount; $i++) {
            $num = $i+1;
            $settings[] = new \SRS\Model\Settings('user_custom_text_'.$i, "Vlastní text přihlášky č.".$num, '');
        }

        return $settings;
    }


}
