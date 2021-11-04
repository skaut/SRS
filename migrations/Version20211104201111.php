<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211104201111 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE ticket_check (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, datetime DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_14BECF98A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ticket_check ADD CONSTRAINT FK_14BECF98A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('INSERT INTO `settings` (`item`, `value`) VALUES (\'tickets_api_token\', NULL)');
    }

    public function down(Schema $schema) : void
    {
    }
}
