<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180908084237 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('ALTER TABLE block CHANGE mandatory mandatory VARCHAR(255) NOT NULL');
        $this->addSql('UPDATE block SET mandatory = "voluntary" WHERE mandatory = "0"');
        $this->addSql('UPDATE block SET mandatory = "mandatory" WHERE mandatory = "1"');
        $this->addSql('UPDATE block SET mandatory = "auto_registered" WHERE mandatory = "2"');
    }

    public function down(Schema $schema): void
    {
    }
}
