<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181005232108 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('CREATE TABLE payment (id INT AUTO_INCREMENT NOT NULL, transaction_id VARCHAR(255) DEFAULT NULL, date DATE NOT NULL, ammount DOUBLE PRECISION NOT NULL, account_number VARCHAR(255) DEFAULT NULL, account_name VARCHAR(255) DEFAULT NULL, variable_symbol VARCHAR(255) DEFAULT NULL, message VARCHAR(255) DEFAULT NULL, state VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE application ADD payment_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE application ADD CONSTRAINT FK_A45BDDC14C3A3BB FOREIGN KEY (payment_id) REFERENCES payment (id)');
        $this->addSql('CREATE INDEX IDX_A45BDDC14C3A3BB ON application (payment_id)');
        $this->addSql('INSERT INTO `resource` (`id`, `name`) VALUES (\'8\', \'payments\')');
        $this->addSql('INSERT INTO `permission` (`id`, `resource_id`, `name`) VALUES (\'14\', \'8\', \'manage\')');
        $this->addSql('INSERT INTO `role_permission` (`role_id`, `permission_id`) VALUES (\'8\', \'14\'), (\'7\', \'14\')');
    }

    public function down(Schema $schema): void
    {
    }
}
