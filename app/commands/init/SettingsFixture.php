<?php

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Nette\Security\Passwords;
use Nette\Utils\Neon;
use App\Model\Settings\Settings;

class SettingsFixture extends AbstractFixture
{
    /**
     * @var \Kdyby\Translation\Translator
     * @inject
     */
    public $translator;

    /**
     * @var \App\ConfigFacade
     * @inject
     */
    public $configFacade;

    public function load(ObjectManager $manager)
    {
        $config = $this->configFacade->loadConfig();
        $customBooleanCount = $config['parameters']['customInputs']['booleanCount'];
        $customTextCount = $config['parameters']['customInputs']['textCount'];

        $today = new \DateTime('now');
        $tommorow = new \DateTime('now');
        $tommorow->modify('+1 day');
        $yesterday = new \DateTime('now');
        $yesterday->modify('-1 day');

        $settings = array();
        $settings[] = new Settings('seminar_name', $this->translator->translate('common.settings.default.seminar_name'));
        $settings[] = new Settings('seminar_email', $this->translator->translate('common.settings.default.seminar_email'));
        $settings[] = new Settings('seminar_from_date', $today->format('d.m.Y'));
        $settings[] = new Settings('seminar_to_date', $tommorow->format('d.m.Y'));

        $settings[] = new Settings('basic_block_duration', '60');
        $settings[] = new Settings('is_allowed_add_block', '1');
        $settings[] = new Settings('is_allowed_modify_schedule', '1');
        $settings[] = new Settings('is_allowed_log_in_programs', '0');
        $settings[] = new Settings('is_allowed_log_in_programs_before_payment', '0');

        $settings[] = new Settings('skautis_action_id', '');
        $settings[] = new Settings('skautis_action_name', '');

        $settings[] = new Settings('logo', '/img/logo.png');
        $settings[] = new Settings('footer', '&copy; SRS ' + $today->format('Y'));

        $settings[] = new Settings('company', $this->translator->translate('common.settings.default.seminar_name'));
        $settings[] = new Settings('ico', $this->translator->translate('common.settings.default.seminar_name'));
        $settings[] = new Settings('accountant', $this->translator->translate('common.settings.default.seminar_name'));
        $settings[] = new Settings('print_location', $this->translator->translate('common.settings.default.seminar_name'));
        $settings[] = new Settings('account_number', $this->translator->translate('common.settings.default.seminar_name'));
        $settings[] = new Settings('variable_symbol_code', '00');

        $settings[] = new Settings('log_in_programs_from', $yesterday->format('d.m.Y H:i'));
        $settings[] = new Settings('log_in_programs_to', $today->format('d.m.Y H:i'));
        $settings[] = new Settings('cancel_registration_to', $today->format('d.m.Y'));

        $settings[] = new Settings('display_users_roles', '1');

        for ($i = 1; $i <= $customBooleanCount; $i++) {
            $settings[] = new Settings('custom_boolean_' . $i, '');
        }

        for ($i = 1; $i <= $customTextCount; $i++) {
            $settings[] = new Settings('custom_text_' . $i, '');
        }

        foreach ($settings as $setting) {
            $manager->persist($setting);
        }

        $manager->flush();
    }
}