<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220415104222 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE mail_batch (id INT AUTO_INCREMENT NOT NULL, sent TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE mail ADD batch_id INT DEFAULT NULL, ADD recipient_emails LONGTEXT NOT NULL COMMENT \'(DC2Type:simple_array)\'');
        $this->addSql('ALTER TABLE mail ADD CONSTRAINT FK_5126AC48F39EBE7A FOREIGN KEY (batch_id) REFERENCES mail_batch (id)');
        $this->addSql('CREATE INDEX IDX_5126AC48F39EBE7A ON mail (batch_id)');
    }

    public function down(Schema $schema): void
    {
    }
}
