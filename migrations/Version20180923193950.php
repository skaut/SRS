<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180923193950 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('ALTER TABLE user CHANGE fee fee INT NOT NULL, CHANGE fee_remaining fee_remaining INT NOT NULL, CHANGE not_registered_mandatory_blocks_count not_registered_mandatory_blocks_count INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
    }
}
