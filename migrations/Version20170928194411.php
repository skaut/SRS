<?php

namespace Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170928194411 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE discount_subevent');
        $this->addSql('ALTER TABLE discount CHANGE condition_operator `condition` VARCHAR(255) NOT NULL');
        $this->addSql('TRUNCATE discount');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
