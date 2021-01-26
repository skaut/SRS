<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */

class Version20171227203925 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('CREATE TABLE application_role (application_id INT NOT NULL, role_id INT NOT NULL, INDEX IDX_A085E2E23E030ACD (application_id), INDEX IDX_A085E2E2D60322AC (role_id), PRIMARY KEY(application_id, role_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE application_subevent (application_id INT NOT NULL, subevent_id INT NOT NULL, INDEX IDX_FE263A6E3E030ACD (application_id), INDEX IDX_FE263A6E7A675502 (subevent_id), PRIMARY KEY(application_id, subevent_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE application_role ADD CONSTRAINT FK_A085E2E23E030ACD FOREIGN KEY (application_id) REFERENCES application (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE application_role ADD CONSTRAINT FK_A085E2E2D60322AC FOREIGN KEY (role_id) REFERENCES role (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE application_subevent ADD CONSTRAINT FK_FE263A6E3E030ACD FOREIGN KEY (application_id) REFERENCES application (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE application_subevent ADD CONSTRAINT FK_FE263A6E7A675502 FOREIGN KEY (subevent_id) REFERENCES subevent (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE roles_application_role');
        $this->addSql('DROP TABLE subevents_application_subevent');
    }

    public function down(Schema $schema): void
    {
    }
}
