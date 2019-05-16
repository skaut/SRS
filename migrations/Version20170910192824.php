<?php

namespace Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170910192824 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE application ADD variable_symbol VARCHAR(255) DEFAULT NULL, ADD application_order INT DEFAULT NULL, ADD maturity_date DATE DEFAULT NULL, ADD first TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE user DROP variable_symbol, DROP application_order');

        $this->addSql('INSERT INTO `settings` (`item`, `value`) VALUES 
                             (\'maturity_type\', \'maturity_days\'),
                             (\'maturity_date\', NULL),
                             (\'maturity_days\', \'7\'),
                             (\'maturity_work_days\', NULL)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
