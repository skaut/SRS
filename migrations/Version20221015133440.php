<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20221015133440 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE patrol (id INT AUTO_INCREMENT NOT NULL, troop_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, INDEX IDX_BFB2371263060AC (troop_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE troop (id INT AUTO_INCREMENT NOT NULL, variable_symbol_id INT DEFAULT NULL, payment_id INT DEFAULT NULL, income_proof_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, fee INT NOT NULL, application_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', maturity_date DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', payment_method VARCHAR(255) DEFAULT NULL, payment_date DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', state VARCHAR(255) NOT NULL, INDEX IDX_FAAD534C25813A9D (variable_symbol_id), INDEX IDX_FAAD534C4C3A3BB (payment_id), INDEX IDX_FAAD534CFE69EDFB (income_proof_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_group_role (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, troop_id INT DEFAULT NULL, patrol_id INT DEFAULT NULL, role_id INT DEFAULT NULL, INDEX IDX_D95417F6A76ED395 (user_id), INDEX IDX_D95417F6263060AC (troop_id), INDEX IDX_D95417F6A7B49BA9 (patrol_id), INDEX IDX_D95417F6D60322AC (role_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE patrol ADD CONSTRAINT FK_BFB2371263060AC FOREIGN KEY (troop_id) REFERENCES troop (id)');
        $this->addSql('ALTER TABLE troop ADD CONSTRAINT FK_FAAD534C25813A9D FOREIGN KEY (variable_symbol_id) REFERENCES variable_symbol (id)');
        $this->addSql('ALTER TABLE troop ADD CONSTRAINT FK_FAAD534C4C3A3BB FOREIGN KEY (payment_id) REFERENCES payment (id)');
        $this->addSql('ALTER TABLE troop ADD CONSTRAINT FK_FAAD534CFE69EDFB FOREIGN KEY (income_proof_id) REFERENCES income_proof (id)');
        $this->addSql('ALTER TABLE user_group_role ADD CONSTRAINT FK_D95417F6A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_group_role ADD CONSTRAINT FK_D95417F6263060AC FOREIGN KEY (troop_id) REFERENCES troop (id)');
        $this->addSql('ALTER TABLE user_group_role ADD CONSTRAINT FK_D95417F6A7B49BA9 FOREIGN KEY (patrol_id) REFERENCES patrol (id)');
        $this->addSql('ALTER TABLE user_group_role ADD CONSTRAINT FK_D95417F6D60322AC FOREIGN KEY (role_id) REFERENCES role (id)');
        $this->addSql('ALTER TABLE role ADD type VARCHAR(255) NOT NULL, ADD minimum_age_warning VARCHAR(255) DEFAULT NULL, ADD maximum_age INT NOT NULL, ADD maximum_age_warning VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
    }
}
