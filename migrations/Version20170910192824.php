<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20170910192824 extends AbstractMigration
{
    public function up(Schema $schema): void
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

    public function down(Schema $schema): void
    {
    }
}
