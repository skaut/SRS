<?php declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181020221331 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE mail_subevent (mail_id INT NOT NULL, subevent_id INT NOT NULL, INDEX IDX_9CCA2435C8776F01 (mail_id), INDEX IDX_9CCA24357A675502 (subevent_id), PRIMARY KEY(mail_id, subevent_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE mail_subevent ADD CONSTRAINT FK_9CCA2435C8776F01 FOREIGN KEY (mail_id) REFERENCES mail (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE mail_subevent ADD CONSTRAINT FK_9CCA24357A675502 FOREIGN KEY (subevent_id) REFERENCES subevent (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
    }
}
