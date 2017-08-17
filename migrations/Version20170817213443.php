<?php

namespace Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170817213443 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE mail_to_roles_role DROP FOREIGN KEY FK_B0EF37624141B2B7');
        $this->addSql('CREATE TABLE mail_role (mail_id INT NOT NULL, role_id INT NOT NULL, INDEX IDX_FA121903C8776F01 (mail_id), INDEX IDX_FA121903D60322AC (role_id), PRIMARY KEY(mail_id, role_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE mail_user (mail_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_20E84520C8776F01 (mail_id), INDEX IDX_20E84520A76ED395 (user_id), PRIMARY KEY(mail_id, user_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE mail_template (id INT AUTO_INCREMENT NOT NULL, string VARCHAR(255) NOT NULL, subject VARCHAR(255) NOT NULL, text LONGTEXT NOT NULL, active TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE mail_role ADD CONSTRAINT FK_FA121903C8776F01 FOREIGN KEY (mail_id) REFERENCES mail (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE mail_role ADD CONSTRAINT FK_FA121903D60322AC FOREIGN KEY (role_id) REFERENCES role (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE mail_user ADD CONSTRAINT FK_20E84520C8776F01 FOREIGN KEY (mail_id) REFERENCES mail (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE mail_user ADD CONSTRAINT FK_20E84520A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE mail_to_roles');
        $this->addSql('DROP TABLE mail_to_roles_role');
        $this->addSql('DROP TABLE mail_to_user');
        $this->addSql('ALTER TABLE mail ADD text LONGTEXT NOT NULL, DROP type');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE mail_to_roles (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE mail_to_roles_role (mail_to_roles_id INT NOT NULL, role_id INT NOT NULL, INDEX IDX_B0EF37624141B2B7 (mail_to_roles_id), INDEX IDX_B0EF3762D60322AC (role_id), PRIMARY KEY(mail_to_roles_id, role_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE mail_to_user (id INT NOT NULL, recipient_user_id INT DEFAULT NULL, INDEX IDX_B0C046E9B15EFB97 (recipient_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE mail_to_roles ADD CONSTRAINT FK_60D5DEBFBF396750 FOREIGN KEY (id) REFERENCES mail (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE mail_to_roles_role ADD CONSTRAINT FK_B0EF37624141B2B7 FOREIGN KEY (mail_to_roles_id) REFERENCES mail_to_roles (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE mail_to_roles_role ADD CONSTRAINT FK_B0EF3762D60322AC FOREIGN KEY (role_id) REFERENCES role (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE mail_to_user ADD CONSTRAINT FK_B0C046E9B15EFB97 FOREIGN KEY (recipient_user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE mail_to_user ADD CONSTRAINT FK_B0C046E9BF396750 FOREIGN KEY (id) REFERENCES mail (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE mail_role');
        $this->addSql('DROP TABLE mail_user');
        $this->addSql('DROP TABLE mail_template');
        $this->addSql('ALTER TABLE mail ADD type VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, DROP text');
    }
}
