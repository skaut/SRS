<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171001065213 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE custom_select (id INT NOT NULL, options VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE custom_select ADD CONSTRAINT FK_7F5F3EC4BF396750 FOREIGN KEY (id) REFERENCES custom_input (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE custom_input ADD mandatory TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
    }
}
