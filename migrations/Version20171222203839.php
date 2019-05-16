<?php declare(strict_types = 1);

namespace Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171222203839 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX UNIQ_E545A0C51F1B251E ON settings');
        $this->addSql('ALTER TABLE application DROP application_order');
        $this->addSql('DELETE FROM `settings` WHERE `settings`.`item` = \'variable_symbol_type\'');
    }

    public function down(Schema $schema) : void
    {
    }
}
