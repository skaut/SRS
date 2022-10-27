<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20221021195625 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE troop ADD leader_id INT DEFAULT NULL, ADD pairing_code VARCHAR(255) NOT NULL, ADD paired_troop_code VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE troop ADD CONSTRAINT FK_FAAD534C73154ED4 FOREIGN KEY (leader_id) REFERENCES user (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FAAD534C73154ED4 ON troop (leader_id)');
        $this->addSql('ALTER TABLE user ADD phone VARCHAR(255) DEFAULT NULL, ADD mother_name VARCHAR(255) DEFAULT NULL, ADD mother_phone VARCHAR(255) DEFAULT NULL, ADD father_name VARCHAR(255) DEFAULT NULL, ADD father_phone VARCHAR(255) DEFAULT NULL, ADD health_info LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
    }
}
