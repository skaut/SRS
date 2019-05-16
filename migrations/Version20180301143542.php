<?php declare(strict_types = 1);

namespace Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180301143542 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE subevent_skaut_is_course (subevent_id INT NOT NULL, skaut_is_course_id INT NOT NULL, INDEX IDX_EF3BE1D37A675502 (subevent_id), INDEX IDX_EF3BE1D3D86001CA (skaut_is_course_id), PRIMARY KEY(subevent_id, skaut_is_course_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE skaut_is_course (id INT AUTO_INCREMENT NOT NULL, skaut_is_course_id INT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE subevent_skaut_is_course ADD CONSTRAINT FK_EF3BE1D37A675502 FOREIGN KEY (subevent_id) REFERENCES subevent (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE subevent_skaut_is_course ADD CONSTRAINT FK_EF3BE1D3D86001CA FOREIGN KEY (skaut_is_course_id) REFERENCES skaut_is_course (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema)
    {
    }
}
