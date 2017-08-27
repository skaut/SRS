<?php

namespace Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170827194801 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE discount (id INT AUTO_INCREMENT NOT NULL, operator VARCHAR(255) NOT NULL, discount INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE discount_subevent (discount_id INT NOT NULL, subevent_id INT NOT NULL, INDEX IDX_2B1BCDE24C7C611F (discount_id), INDEX IDX_2B1BCDE27A675502 (subevent_id), PRIMARY KEY(discount_id, subevent_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE subevent (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, implicit TINYINT(1) NOT NULL, fee INT NOT NULL, capacity INT DEFAULT NULL, UNIQUE INDEX UNIQ_411DE57D5E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE subevent_subevent_incompatible (subevent_id INT NOT NULL, incompatible_subevent_id INT NOT NULL, INDEX IDX_D89E46427A675502 (subevent_id), INDEX IDX_D89E46428CEDA9BB (incompatible_subevent_id), PRIMARY KEY(subevent_id, incompatible_subevent_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE subevent_subevent_required (subevent_id INT NOT NULL, required_subevent_id INT NOT NULL, INDEX IDX_91468A527A675502 (subevent_id), INDEX IDX_91468A523A243992 (required_subevent_id), PRIMARY KEY(subevent_id, required_subevent_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE application (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, application_date DATETIME NOT NULL, payment_method VARCHAR(255) DEFAULT NULL, payment_date DATE DEFAULT NULL, state VARCHAR(255) NOT NULL, INDEX IDX_A45BDDC1A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE application_subevent (application_id INT NOT NULL, subevent_id INT NOT NULL, INDEX IDX_FE263A6E3E030ACD (application_id), INDEX IDX_FE263A6E7A675502 (subevent_id), PRIMARY KEY(application_id, subevent_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE discount_subevent ADD CONSTRAINT FK_2B1BCDE24C7C611F FOREIGN KEY (discount_id) REFERENCES discount (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE discount_subevent ADD CONSTRAINT FK_2B1BCDE27A675502 FOREIGN KEY (subevent_id) REFERENCES subevent (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE subevent_subevent_incompatible ADD CONSTRAINT FK_D89E46427A675502 FOREIGN KEY (subevent_id) REFERENCES subevent (id)');
        $this->addSql('ALTER TABLE subevent_subevent_incompatible ADD CONSTRAINT FK_D89E46428CEDA9BB FOREIGN KEY (incompatible_subevent_id) REFERENCES subevent (id)');
        $this->addSql('ALTER TABLE subevent_subevent_required ADD CONSTRAINT FK_91468A527A675502 FOREIGN KEY (subevent_id) REFERENCES subevent (id)');
        $this->addSql('ALTER TABLE subevent_subevent_required ADD CONSTRAINT FK_91468A523A243992 FOREIGN KEY (required_subevent_id) REFERENCES subevent (id)');
        $this->addSql('ALTER TABLE application ADD CONSTRAINT FK_A45BDDC1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE application_subevent ADD CONSTRAINT FK_FE263A6E3E030ACD FOREIGN KEY (application_id) REFERENCES application (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE application_subevent ADD CONSTRAINT FK_FE263A6E7A675502 FOREIGN KEY (subevent_id) REFERENCES subevent (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE block ADD subevent_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE block ADD CONSTRAINT FK_831B97227A675502 FOREIGN KEY (subevent_id) REFERENCES subevent (id)');
        $this->addSql('CREATE INDEX IDX_831B97227A675502 ON block (subevent_id)');
        $this->addSql('ALTER TABLE user DROP application_date, DROP payment_method, DROP payment_date');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
