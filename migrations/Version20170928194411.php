<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170928194411 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE discount_subevent');
        $this->addSql('ALTER TABLE discount CHANGE condition_operator `condition` VARCHAR(255) NOT NULL');
        $this->addSql('TRUNCATE discount');
    }

    public function down(Schema $schema): void
    {
    }
}
