<?php declare(strict_types = 1);

namespace Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171223230501 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE mail_template ADD send_to_user TINYINT(1) NOT NULL, ADD send_to_organizer TINYINT(1) NOT NULL');

        $this->addSql('INSERT INTO `mail_template_variable` (`id`, `name`) VALUES (NULL, \'user\')');

        $this->addSql('INSERT INTO `mail_template` (`id`, `type`, `subject`, `text`, `active`) VALUES (NULL, \'custom_input_value_changed\', \'%nazev-seminare%: změna vlastních polí\', \'Uživatel %uzivatel% si aktualizoval údaje ve vlastních polích přihlášky.\', \'1\')');

        $this->addSql('INSERT INTO `template_template_variable` (`template_id`, `template_variable_id`) VALUES (\'11\', \'1\')');
        $this->addSql('INSERT INTO `template_template_variable` (`template_id`, `template_variable_id`) VALUES (\'11\', \'12\')');
    }

    public function down(Schema $schema) : void
    {
    }
}
