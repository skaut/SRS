<?php

namespace Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170904190024 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE application ADD income_proof_printed_date DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE user DROP income_proof_printed_date');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
