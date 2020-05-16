<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200514180536 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE role CHANGE `system` system_role TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE mail_template CHANGE `system` system_template TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema) : void
    {
    }
}
