<?php

namespace App\Commands\Fixtures;

use App\Model\Settings\Settings;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Kdyby\Translation\Translator;


/**
 * Vytváří počáteční nastavení.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SettingsFixture extends AbstractFixture
{
    /** @var Translator */
    protected $translator;


    /**
     * SettingsFixture constructor.
     * @param Translator $translator
     */
    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Vytváří počáteční data.
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $today = new \DateTime();
        $tommorow = new \DateTime();
        $tommorow->modify('+1 day');
        $yesterday = new \DateTime();
        $yesterday->modify('-1 day');

        $settings = [];
        $settings[] = new Settings(Settings::ADMIN_CREATED, '0');

        $settings[] = new Settings(Settings::SEMINAR_NAME, $this->translator->translate('common.settings.default_value.seminar_name'));
        $settings[] = new Settings(Settings::SEMINAR_EMAIL, $this->translator->translate('common.settings.default_value.seminar_email'));
        $settings[] = new Settings(Settings::SEMINAR_FROM_DATE, $today->format('Y-m-d'));
        $settings[] = new Settings(Settings::SEMINAR_TO_DATE, $tommorow->format('Y-m-d'));

        $settings[] = new Settings(Settings::IS_ALLOWED_ADD_BLOCK, '1');
        $settings[] = new Settings(Settings::IS_ALLOWED_MODIFY_SCHEDULE, '1');
        $settings[] = new Settings(Settings::IS_ALLOWED_REGISTER_PROGRAMS, '0');
        $settings[] = new Settings(Settings::IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT, '0');

        $settings[] = new Settings(Settings::SKAUTIS_EVENT_ID, null);
        $settings[] = new Settings(Settings::SKAUTIS_EVENT_NAME, null);

        $settings[] = new Settings(Settings::LOGO, 'logo.png');
        $settings[] = new Settings(Settings::FOOTER, $this->translator->translate('common.settings.default_value.footer', ['year' => $today->format('Y')]));

        $settings[] = new Settings(Settings::COMPANY, $this->translator->translate('common.settings.default_value.company'));
        $settings[] = new Settings(Settings::ICO, $this->translator->translate('common.settings.default_value.ico'));
        $settings[] = new Settings(Settings::ACCOUNTANT, $this->translator->translate('common.settings.default_value.accountant'));
        $settings[] = new Settings(Settings::PRINT_LOCATION, $this->translator->translate('common.settings.default_value.print_location'));
        $settings[] = new Settings(Settings::ACCOUNT_NUMBER, $this->translator->translate('common.settings.default_value.account_number'));
        $settings[] = new Settings(Settings::VARIABLE_SYMBOL_CODE, '00');

        $settings[] = new Settings(Settings::REGISTER_PROGRAMS_FROM, $yesterday->format(\DateTime::ISO8601));
        $settings[] = new Settings(Settings::REGISTER_PROGRAMS_TO, $today->format(\DateTime::ISO8601));
        $settings[] = new Settings(Settings::EDIT_REGISTRATION_TO, $yesterday->format('Y-m-d'));

        $settings[] = new Settings(Settings::DISPLAY_USERS_ROLES, '1');
        $settings[] = new Settings(Settings::REDIRECT_AFTER_LOGIN, '/');

        $settings[] = new Settings(Settings::PLACE_DESCRIPTION, null);

        foreach ($settings as $setting) {
            $manager->persist($setting);
        }

        $manager->flush();
    }
}