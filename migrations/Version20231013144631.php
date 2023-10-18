<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231013144631 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE mail_queue (id INT AUTO_INCREMENT NOT NULL, mail_id INT DEFAULT NULL, recipient_email VARCHAR(255) NOT NULL, recipient_name VARCHAR(255) DEFAULT NULL, sent TINYINT(1) NOT NULL, enqueue_datetime DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', send_datetime DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_4B3EDD0CC8776F01 (mail_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE mail_queue ADD CONSTRAINT FK_4B3EDD0CC8776F01 FOREIGN KEY (mail_id) REFERENCES mail (id)');
    }

    public function down(Schema $schema): void
    {
    }
}
