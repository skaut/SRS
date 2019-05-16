<?php declare(strict_types = 1);

namespace Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171224193507 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE variable_symbol (id INT AUTO_INCREMENT NOT NULL, variable_symbol VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE application ADD created_by_id INT DEFAULT NULL, ADD valid_from DATETIME NOT NULL, ADD valid_to DATETIME NOT NULL');
        $this->addSql('ALTER TABLE application ADD CONSTRAINT FK_A45BDDC1B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_A45BDDC1B03A8386 ON application (created_by_id)');
        $this->addSql('ALTER TABLE user ADD first_login DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
    }
}
