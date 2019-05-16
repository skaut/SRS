<?php declare(strict_types = 1);

namespace Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171226215255 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE roles_application_role (roles_application_id INT NOT NULL, role_id INT NOT NULL, INDEX IDX_81291939DB4E35D (roles_application_id), INDEX IDX_8129193D60322AC (role_id), PRIMARY KEY(roles_application_id, role_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE subevents_application_subevent (subevents_application_id INT NOT NULL, subevent_id INT NOT NULL, INDEX IDX_71BC0FE777FDE691 (subevents_application_id), INDEX IDX_71BC0FE77A675502 (subevent_id), PRIMARY KEY(subevents_application_id, subevent_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE roles_application_role ADD CONSTRAINT FK_81291939DB4E35D FOREIGN KEY (roles_application_id) REFERENCES application (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE roles_application_role ADD CONSTRAINT FK_8129193D60322AC FOREIGN KEY (role_id) REFERENCES role (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE subevents_application_subevent ADD CONSTRAINT FK_71BC0FE777FDE691 FOREIGN KEY (subevents_application_id) REFERENCES application (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE subevents_application_subevent ADD CONSTRAINT FK_71BC0FE77A675502 FOREIGN KEY (subevent_id) REFERENCES subevent (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE application_role');
        $this->addSql('DROP TABLE application_subevent');
    }

    public function down(Schema $schema)
    {
    }
}
