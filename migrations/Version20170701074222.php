<?php

namespace Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170701074222 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user CHANGE first_login application_date DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD application_order INT DEFAULT NULL');
        $this->addSql('INSERT INTO `settings` (`item`, `value`) VALUES (\'variable_symbol_type\', \'birth_date\')');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
