<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 15.11.12
 * Time: 13:25
 * To change this template use File | Settings | File Templates.
 */
namespace SRS\Factory;

class SettingsFactory
{

    public static function create() {
        $settings = array();
        $settings[] = new \SRS\Model\Settings('superadmin_created', 'Je vytvořen superadmin?', '0');
        $settings[] = new \SRS\Model\Settings('schema_imported', 'Naimportována inicializační databázová data', '1');
        $settings[] = new \SRS\Model\Settings('seminar_name', 'Jméno semináře', 'SRS');
        $today = new \DateTime('now');
        $tommorow = new \DateTime('now');
        $tommorow->modify('+1 day');
        $settings[] = new \SRS\Model\Settings('seminar_from_date', 'Začátek semináře', $today->format('Y-m-d'));
        $settings[] = new \SRS\Model\Settings('seminar_to_date', 'Konec semináře', $tommorow->format('Y-m-d'));
        $settings[] = new \SRS\Model\Settings('basic_block_duration', 'Základní délka trvání jednoho bloku semináře (minuty)', '60');
        $settings[] = new \SRS\Model\Settings('is_allowed_add_block', 'Lze vytvářet programové bloky?', '1');
        $settings[] = new \SRS\Model\Settings('is_allowed_modify_schedule', 'Lze upravovat harmonogram semináře?', '1');
        $settings[] = new \SRS\Model\Settings('is_allowed_log_in_programs', 'Lze se přihlašovat na Programy?', '0');

       // $settings[] = new \SRS\Model\Settings('skautis_app_id', 'skautis app id', '');
        $settings[] = new \SRS\Model\Settings('skautis_seminar_id', 'skautis seminar id', '');

        $settings[] = new \SRS\Model\Settings('logo', 'Logo', '/img/logo.png');
        $settings[] = new \SRS\Model\Settings('footer', 'Patička', '&copy; SRS 2013');

        $settings[] = new \SRS\Model\Settings('company', 'Firma', 'Konfigurace->Firma');
        $settings[] = new \SRS\Model\Settings('ico', 'IČO', 'Konfigurace->IČO');
        $settings[] = new \SRS\Model\Settings('accountant', 'Pokladník', 'Konfigurace->Pokladník');



        $settings[] = new \SRS\Model\Settings('user_custom_boolean_0', 'Vlastní checkbox přihlášky č.1', '');
        $settings[] = new \SRS\Model\Settings('user_custom_boolean_1', 'Vlastní checkbox přihlášky č.2', '');
        $settings[] = new \SRS\Model\Settings('user_custom_boolean_2', 'Vlastní checkbox přihlášky č.3', '');
        $settings[] = new \SRS\Model\Settings('user_custom_boolean_3', 'Vlastní checkbox přihlášky č.4', '');
        $settings[] = new \SRS\Model\Settings('user_custom_boolean_4', 'Vlastní checkbox přihlášky č.5', '');

        $settings[] = new \SRS\Model\Settings('user_custom_text_0', 'Vlastní text přihlášky č.1', '');
        $settings[] = new \SRS\Model\Settings('user_custom_text_1', 'Vlastní text přihlášky č.2', '');

        return $settings;
    }







}
