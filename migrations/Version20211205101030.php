<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211205101030 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ticket_check ADD subevent_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE ticket_check ADD CONSTRAINT FK_14BECF987A675502 FOREIGN KEY (subevent_id) REFERENCES subevent (id)');
        $this->addSql('CREATE INDEX IDX_14BECF987A675502 ON ticket_check (subevent_id)');
    }

    public function down(Schema $schema) : void
    {
    }
}
