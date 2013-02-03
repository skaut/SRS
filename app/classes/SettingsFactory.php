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
        $settings[] = new \SRS\Model\Settings('seminar_from_date', 'Začátek semináře', $today->format('Y-m-d'));
        $settings[] = new \SRS\Model\Settings('seminar_to_date', 'Konec semináře');
        $settings[] = new \SRS\Model\Settings('basic_block_duration', 'Základní délka trvání jednoho bloku semináře (minuty)', '60');
        return $settings;
    }







}
