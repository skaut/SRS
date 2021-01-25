<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210125223746 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE program CHANGE occupancy attendees_count INT NOT NULL');

        $this->addSql('UPDATE program p SET attendees_count = (SELECT count(*) FROM program_application pa WHERE pa.program_id = p.id AND pa.alternate = false)');
    }

    public function down(Schema $schema) : void
    {
    }
}
