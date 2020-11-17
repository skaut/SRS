<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201114190413 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE contact_form_content (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE contact_form_content ADD CONSTRAINT FK_E31E62A6BF396750 FOREIGN KEY (id) REFERENCES content (id) ON DELETE CASCADE');
        $this->addSql('INSERT INTO `settings` (`item`, `value`) VALUES (\'contact_form_recipients\', \'a:1:{i:0;s:12:"srs@skaut.cz";}\')');
        $this->addSql('ALTER TABLE mail_template DROP send_to_user, DROP send_to_organizer');

        $this->addSql('INSERT INTO `mail_template_variable` (`id`, `name`) VALUES (NULL, \'sender_name\'), (NULL, \'sender_email\'), (NULL, \'message\')');
        $this->addSql('INSERT INTO `mail_template` (`id`, `type`, `subject`, `text`, `active`, `system_template`) VALUES (NULL, \'contact_form\', \'%nazev-seminare%: zpráva z kontaktního formuláře\', \'<p>Jméno odesílatele: %jmeno-odesilatele%</p><p>E-mail odesílatele: %email-odesilatele%</p><p>Zpráva:</p><p>%zprava%</p>\', \'1\', \'1\')');
        $this->addSql('INSERT INTO `template_template_variable` (`template_id`, `template_variable_id`) VALUES (\'12\', \'1\'), (\'12\', \'13\'), (\'12\', \'14\'), (\'12\', \'15\')');
    }

    public function down(Schema $schema) : void
    {
    }
}
