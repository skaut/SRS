<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20170701074222 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user CHANGE first_login application_date DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD application_order INT DEFAULT NULL');
        $this->addSql('INSERT INTO `settings` (`item`, `value`) VALUES (\'variable_symbol_type\', \'birth_date\')');
    }

    public function down(Schema $schema): void
    {
    }
}
