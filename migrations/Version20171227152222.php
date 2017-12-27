<?php declare(strict_types = 1);

namespace Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171227152222 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('UPDATE `mail_template` SET `text` = \'Byl/a jsi přihlášen/a na web akce: %nazev-seminare%. Toto přihlášení není registrací k účasti na akci. Pokud se chceš akce zúčastnit, tak se nezapomeň na webu zaregistrovat.\' WHERE `mail_template`.`id` = 1');
        $this->addSql('UPDATE `mail_template` SET `text` = \'Byl/a jsi zaregistrován/a na akci: %nazev-seminare%. Nezapomeň zaplatit registrační poplatek. Více informací o platbě najdeš na webu ve svém profilu. Zrušit svou registraci můžeš přes web do: %zmena-registrace-do%.\' WHERE `mail_template`.`id` = 2');
        $this->addSql('UPDATE `mail_template` SET `text` = \'Tvoje účast na akci: %nazev-seminare% byla zrušena. Pokud jde o omyl, tak se znovu zaregistruj na webu.\' WHERE `mail_template`.`id` = 3');
        $this->addSql('UPDATE `mail_template` SET `type` = \'roles_changed\', `subject` = \'%nazev-seminare%: změna rolí\', `text` = \'Na akci: %nazev-seminare%, na kterou jsi se již dříve registroval/a, Ti byly změněny role. Tvoje stávájící role jsou: %role%.\' WHERE `mail_template`.`id` = 4');
        $this->addSql('UPDATE `mail_template` SET `type` = \'subevents_changed\', `subject` = \'%nazev-seminare%: změna podakcí\', `text` = \'Na akci: %nazev-seminare%, na kterou jsi se již dříve registroval/a, Ti byly změněny podakce. Tvoje stávájící podakce jsou: %podakce%.\' WHERE `mail_template`.`id` = 5');
        $this->addSql('UPDATE `mail_template` SET `text` = \'Tvůj poplatek za účast na akci: %nazev-seminare% byl přijat organizátorem. Po spuštění přihlašování na programy si budeš moci na webu vybírat jednotlivé programy.\' WHERE `mail_template`.`id` = 6');
        $this->addSql('UPDATE `mail_template` SET `text` = \'Blíží se splatnost poplatku za Tvou účast na akci: %nazev-seminare%. Pokud neobdržíme Tvou platbu do: %splatnost%, tak bude Tvé místo uvolněno dalšímu zájemci.\' WHERE `mail_template`.`id` = 7');
        $this->addSql('UPDATE `mail_template` SET `text` = \'Byl jsi přhlášen na program: \"%nazev-programu%\", zkontroluj si svůj rozvrh.\' WHERE `mail_template`.`id` = 8');
        $this->addSql('UPDATE `mail_template` SET `text` = \'Byl jsi odhlášen z programu: \"%nazev-programu%\", zkontroluj si svůj rozvrh.\' WHERE `mail_template`.`id` = 9');
        $this->addSql('UPDATE `mail_template` SET `text` = \'Uživateli: %uzivatel% byly aktualizovány údaje ve vlastních polích přihlášky.\' WHERE `mail_template`.`id` = 11');
        $this->addSql('DELETE FROM `template_template_variable` WHERE `template_template_variable`.`template_id` = 4 AND `template_template_variable`.`template_variable_id` = 4');
    }

    public function down(Schema $schema)
    {
    }
}
