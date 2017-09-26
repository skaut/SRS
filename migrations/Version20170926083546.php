<?php

namespace Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170926083546 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('UPDATE `mail_template` SET `type` = \'subevent_added\', `subject` = \'%nazev-seminare%: přidání podakcí\', `text` = \'Na akci %nazev-seminare%, na kterou jsi se již dříve registroval/a jsi přidal další podakce. Tvoje stávájící podakce jsou %podakce%.\', `active` = \'1\' WHERE `mail_template`.`id` = 5');
        $this->addSql('UPDATE `mail_template` SET `type` = \'registration_changed\', `subject` = \'%nazev-seminare%: změna registrace\', `text` = \'Na akci %nazev-seminare%, na kterou jsi se již dříve registroval/a jsi změnil svou roli nebo podakce. Tvoje stávájící role jsou %role% a podakce %podakce%.\' WHERE `mail_template`.`id` = 4');
        $this->addSql('UPDATE `mail_template` SET `active` = \'1\' WHERE `mail_template`.`id` = 7');
        $this->addSql('INSERT INTO `template_template_variable` (`template_id`, `template_variable_id`) VALUES (\'4\', \'4\')');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
