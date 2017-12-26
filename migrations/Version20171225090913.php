<?php declare(strict_types = 1);

namespace Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171225090913 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE roles_application (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(255) NOT NULL, fee INT NOT NULL, application_date DATETIME NOT NULL, maturity_date DATE DEFAULT NULL, payment_method VARCHAR(255) DEFAULT NULL, payment_date DATE DEFAULT NULL, income_proof_printed_date DATE DEFAULT NULL, state VARCHAR(255) NOT NULL, valid_from DATETIME NOT NULL, valid_to DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE subevents_application (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(255) NOT NULL, fee INT NOT NULL, application_date DATETIME NOT NULL, maturity_date DATE DEFAULT NULL, payment_method VARCHAR(255) DEFAULT NULL, payment_date DATE DEFAULT NULL, income_proof_printed_date DATE DEFAULT NULL, state VARCHAR(255) NOT NULL, valid_from DATETIME NOT NULL, valid_to DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE application ADD type VARCHAR(255) NOT NULL, DROP first');
    }

    public function down(Schema $schema)
    {
    }
}
