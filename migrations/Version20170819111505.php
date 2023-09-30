<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20170819111505 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('INSERT INTO `mail_template_variable` (`id`, `name`) VALUES (NULL, \'seminar_name\')');
        $this->addSql('INSERT INTO `mail_template_variable` (`id`, `name`) VALUES (NULL, \'edit_registration_to\')');
        $this->addSql('INSERT INTO `mail_template_variable` (`id`, `name`) VALUES (NULL, \'users_roles\')');
        $this->addSql('INSERT INTO `mail_template_variable` (`id`, `name`) VALUES (NULL, \'users_subevents\')');
        $this->addSql('INSERT INTO `mail_template_variable` (`id`, `name`) VALUES (NULL, \'maturity\')');
        $this->addSql('INSERT INTO `mail_template_variable` (`id`, `name`) VALUES (NULL, \'program_name\')');

        $this->addSql('INSERT INTO `mail_template` (`id`, `type`, `subject`, `text`, `active`) VALUES (NULL, \'sign_in\', \'%nazev-seminare%: přihlášení na web\', \'Byl/a jsi přihlášen/a na web akce %nazev-seminare%. Toto přihlášení není registrací k účasti na akci. Pokud se chceš akce zúčastnit, tak se nezapomeň na webu zaregistrovat.\', \'0\')');
        $this->addSql('INSERT INTO `mail_template` (`id`, `type`, `subject`, `text`, `active`) VALUES (NULL, \'registration\', \'%nazev-seminare%: registrace na seminář\', \'Byl/a jsi zaregistrován/a na akci %nazev-seminare%. Nezapomeň zaplatit registrační poplatek. Více informací o platbě najdeš na webu ve svém Profilu. Zrušit svou registraci můžeš přes web do %zmena-registrace-do%.\', \'1\')');
        $this->addSql('INSERT INTO `mail_template` (`id`, `type`, `subject`, `text`, `active`) VALUES (NULL, \'registration_canceled\', \'%nazev-seminare%: odhlášení ze semináře\', \'Tvoje účast na akci %nazev-seminare% byla zrušena. Pokud jde o omyl, tak se znovu zaregistruj na webu.\', \'1\')');
        $this->addSql('INSERT INTO `mail_template` (`id`, `type`, `subject`, `text`, `active`) VALUES (NULL, \'role_changed\', \'%nazev-seminare%: změna rolí\', \'Na akci %nazev-seminare%, na kterou jsi se již dříve registroval/a jsi změnil svou roli. Tvoje stávájící role jsou %role%.\', \'1\')');
        $this->addSql('INSERT INTO `mail_template` (`id`, `type`, `subject`, `text`, `active`) VALUES (NULL, \'subevent_changed\', \'%nazev-seminare%: změna podakcí\', \'\', \'0\')');
        $this->addSql('INSERT INTO `mail_template` (`id`, `type`, `subject`, `text`, `active`) VALUES (NULL, \'payment_confirmed\', \'%nazev-seminare%: potvrzení přijetí platby\', \'Tvůj poplatek za účast na akci %nazev-seminare% byl přijat organizátorem. Po spuštění přihlašování na programy si budeš moci na webu vybírat jednotlivé programy.\', \'1\')');
        $this->addSql('INSERT INTO `mail_template` (`id`, `type`, `subject`, `text`, `active`) VALUES (NULL, \'maturity_notice\', \'%nazev-seminare%: připomenutí splatnosti\', \'Blíží se splatnost poplatku za Tvou účast na akci %nazev-seminare%. Pokud neobdržíme Tvou platbu do %splatnost%, tak bude Tvé místo uvolněno dalšímu zájemci.\', \'0\')');
        $this->addSql('INSERT INTO `mail_template` (`id`, `type`, `subject`, `text`, `active`) VALUES (NULL, \'program_registered\', \'%nazev-seminare%: přihlášení na program\', \'Byl jsi přhlášen na program "%nazev-programu%", zkontroluj si svůj rozvrh.\', \'1\')');
        $this->addSql('INSERT INTO `mail_template` (`id`, `type`, `subject`, `text`, `active`) VALUES (NULL, \'program_unregistered\', \'%nazev-seminare%: odhlášení z programu\', \'Byl jsi odhlášen z programu "%nazev-programu%", zkontroluj si svůj rozvrh.\', \'1\')');

        $this->addSql('INSERT INTO `template_template_variable` (`template_id`, `template_variable_id`) VALUES (\'1\', \'1\')');

        $this->addSql('INSERT INTO `template_template_variable` (`template_id`, `template_variable_id`) VALUES (\'2\', \'1\')');
        $this->addSql('INSERT INTO `template_template_variable` (`template_id`, `template_variable_id`) VALUES (\'2\', \'2\')');

        $this->addSql('INSERT INTO `template_template_variable` (`template_id`, `template_variable_id`) VALUES (\'3\', \'1\')');

        $this->addSql('INSERT INTO `template_template_variable` (`template_id`, `template_variable_id`) VALUES (\'4\', \'1\')');
        $this->addSql('INSERT INTO `template_template_variable` (`template_id`, `template_variable_id`) VALUES (\'4\', \'3\')');

        $this->addSql('INSERT INTO `template_template_variable` (`template_id`, `template_variable_id`) VALUES (\'5\', \'1\')');
        $this->addSql('INSERT INTO `template_template_variable` (`template_id`, `template_variable_id`) VALUES (\'5\', \'4\')');

        $this->addSql('INSERT INTO `template_template_variable` (`template_id`, `template_variable_id`) VALUES (\'6\', \'1\')');

        $this->addSql('INSERT INTO `template_template_variable` (`template_id`, `template_variable_id`) VALUES (\'7\', \'1\')');
        $this->addSql('INSERT INTO `template_template_variable` (`template_id`, `template_variable_id`) VALUES (\'7\', \'5\')');

        $this->addSql('INSERT INTO `template_template_variable` (`template_id`, `template_variable_id`) VALUES (\'8\', \'1\')');
        $this->addSql('INSERT INTO `template_template_variable` (`template_id`, `template_variable_id`) VALUES (\'8\', \'6\')');

        $this->addSql('INSERT INTO `template_template_variable` (`template_id`, `template_variable_id`) VALUES (\'9\', \'1\')');
        $this->addSql('INSERT INTO `template_template_variable` (`template_id`, `template_variable_id`) VALUES (\'9\', \'6\')');
    }

    public function down(Schema $schema): void
    {
    }
}
