<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231115155623 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE `group` (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, leader_id INT NOT NULL, leader_email VARCHAR(255) NOT NULL, create_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', group_status_id INT NOT NULL, places VARCHAR(255) NOT NULL, price INT NOT NULL, note LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE status (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_7B00651C5E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE template_template_variable ADD CONSTRAINT FK_62257D3C5DA0FB8 FOREIGN KEY (template_id) REFERENCES mail_template (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE template_template_variable ADD CONSTRAINT FK_62257D3CF8FA6AEA FOREIGN KEY (template_variable_id) REFERENCES mail_template_variable (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE role_permission ADD CONSTRAINT FK_6F7DF886D60322AC FOREIGN KEY (role_id) REFERENCES role (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE role_permission ADD CONSTRAINT FK_6F7DF886FED90CCA FOREIGN KEY (permission_id) REFERENCES permission (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE role_role_incompatible ADD CONSTRAINT FK_30A89EE7D60322AC FOREIGN KEY (role_id) REFERENCES role (id)');
        $this->addSql('ALTER TABLE role_role_incompatible ADD CONSTRAINT FK_30A89EE7657B53D5 FOREIGN KEY (incompatible_role_id) REFERENCES role (id)');
        $this->addSql('ALTER TABLE role_role_required ADD CONSTRAINT FK_45B98BABD60322AC FOREIGN KEY (role_id) REFERENCES role (id)');
        $this->addSql('ALTER TABLE role_role_required ADD CONSTRAINT FK_45B98BABF7E2056C FOREIGN KEY (required_role_id) REFERENCES role (id)');
        $this->addSql('ALTER TABLE slideshow_content ADD CONSTRAINT FK_3001DAD3BF396750 FOREIGN KEY (id) REFERENCES content (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE subevent_subevent_incompatible ADD CONSTRAINT FK_D89E46427A675502 FOREIGN KEY (subevent_id) REFERENCES subevent (id)');
        $this->addSql('ALTER TABLE subevent_subevent_incompatible ADD CONSTRAINT FK_D89E46428CEDA9BB FOREIGN KEY (incompatible_subevent_id) REFERENCES subevent (id)');
        $this->addSql('ALTER TABLE subevent_subevent_required ADD CONSTRAINT FK_91468A527A675502 FOREIGN KEY (subevent_id) REFERENCES subevent (id)');
        $this->addSql('ALTER TABLE subevent_subevent_required ADD CONSTRAINT FK_91468A523A243992 FOREIGN KEY (required_subevent_id) REFERENCES subevent (id)');
        $this->addSql('ALTER TABLE subevent_skaut_is_course ADD CONSTRAINT FK_EF3BE1D37A675502 FOREIGN KEY (subevent_id) REFERENCES subevent (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE subevent_skaut_is_course ADD CONSTRAINT FK_EF3BE1D3D86001CA FOREIGN KEY (skaut_is_course_id) REFERENCES skaut_is_course (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tag_role ADD CONSTRAINT FK_B96635DCBAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tag_role ADD CONSTRAINT FK_B96635DCD60322AC FOREIGN KEY (role_id) REFERENCES role (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE text_content ADD CONSTRAINT FK_DA641F96BF396750 FOREIGN KEY (id) REFERENCES content (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ticket_check ADD CONSTRAINT FK_14BECF98A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE ticket_check ADD CONSTRAINT FK_14BECF987A675502 FOREIGN KEY (subevent_id) REFERENCES subevent (id)');
        $this->addSql('ALTER TABLE user_role ADD CONSTRAINT FK_2DE8C6A3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_role ADD CONSTRAINT FK_2DE8C6A3D60322AC FOREIGN KEY (role_id) REFERENCES role (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_block ADD CONSTRAINT FK_61D96C7AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_block ADD CONSTRAINT FK_61D96C7AE9ED820C FOREIGN KEY (block_id) REFERENCES block (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE users_content ADD CONSTRAINT FK_EC54463BF396750 FOREIGN KEY (id) REFERENCES content (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE users_content_role ADD CONSTRAINT FK_C94F41A353CB0C79 FOREIGN KEY (users_content_id) REFERENCES users_content (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE users_content_role ADD CONSTRAINT FK_C94F41A3D60322AC FOREIGN KEY (role_id) REFERENCES role (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE `group`');
        $this->addSql('DROP TABLE status');
        $this->addSql('ALTER TABLE role_permission DROP FOREIGN KEY FK_6F7DF886D60322AC');
        $this->addSql('ALTER TABLE role_permission DROP FOREIGN KEY FK_6F7DF886FED90CCA');
        $this->addSql('ALTER TABLE role_role_incompatible DROP FOREIGN KEY FK_30A89EE7D60322AC');
        $this->addSql('ALTER TABLE role_role_incompatible DROP FOREIGN KEY FK_30A89EE7657B53D5');
        $this->addSql('ALTER TABLE role_role_required DROP FOREIGN KEY FK_45B98BABD60322AC');
        $this->addSql('ALTER TABLE role_role_required DROP FOREIGN KEY FK_45B98BABF7E2056C');
        $this->addSql('ALTER TABLE slideshow_content DROP FOREIGN KEY FK_3001DAD3BF396750');
        $this->addSql('ALTER TABLE subevent_skaut_is_course DROP FOREIGN KEY FK_EF3BE1D37A675502');
        $this->addSql('ALTER TABLE subevent_skaut_is_course DROP FOREIGN KEY FK_EF3BE1D3D86001CA');
        $this->addSql('ALTER TABLE subevent_subevent_incompatible DROP FOREIGN KEY FK_D89E46427A675502');
        $this->addSql('ALTER TABLE subevent_subevent_incompatible DROP FOREIGN KEY FK_D89E46428CEDA9BB');
        $this->addSql('ALTER TABLE subevent_subevent_required DROP FOREIGN KEY FK_91468A527A675502');
        $this->addSql('ALTER TABLE subevent_subevent_required DROP FOREIGN KEY FK_91468A523A243992');
        $this->addSql('ALTER TABLE tag_role DROP FOREIGN KEY FK_B96635DCBAD26311');
        $this->addSql('ALTER TABLE tag_role DROP FOREIGN KEY FK_B96635DCD60322AC');
        $this->addSql('ALTER TABLE template_template_variable DROP FOREIGN KEY FK_62257D3C5DA0FB8');
        $this->addSql('ALTER TABLE template_template_variable DROP FOREIGN KEY FK_62257D3CF8FA6AEA');
        $this->addSql('ALTER TABLE text_content DROP FOREIGN KEY FK_DA641F96BF396750');
        $this->addSql('ALTER TABLE ticket_check DROP FOREIGN KEY FK_14BECF98A76ED395');
        $this->addSql('ALTER TABLE ticket_check DROP FOREIGN KEY FK_14BECF987A675502');
        $this->addSql('ALTER TABLE user_block DROP FOREIGN KEY FK_61D96C7AA76ED395');
        $this->addSql('ALTER TABLE user_block DROP FOREIGN KEY FK_61D96C7AE9ED820C');
        $this->addSql('ALTER TABLE user_role DROP FOREIGN KEY FK_2DE8C6A3A76ED395');
        $this->addSql('ALTER TABLE user_role DROP FOREIGN KEY FK_2DE8C6A3D60322AC');
        $this->addSql('ALTER TABLE users_content DROP FOREIGN KEY FK_EC54463BF396750');
        $this->addSql('ALTER TABLE users_content_role DROP FOREIGN KEY FK_C94F41A353CB0C79');
        $this->addSql('ALTER TABLE users_content_role DROP FOREIGN KEY FK_C94F41A3D60322AC');
    }
}
