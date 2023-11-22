<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180913022739 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('ALTER TABLE program ADD occupancy INT NOT NULL');
        $this->addSql('ALTER TABLE role ADD occupancy INT NOT NULL');
        $this->addSql('ALTER TABLE subevent ADD occupancy INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
    }
}
