<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20170904190024 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE application ADD income_proof_printed_date DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE user DROP income_proof_printed_date');
    }

    public function down(Schema $schema): void
    {
    }
}
