<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20191106203306 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE UNIQUE INDEX UNIQ_6D28840D2FC0CB0F ON payment (transaction_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4AB7DECB8CDE5729 ON mail_template (type)');
    }

    public function down(Schema $schema): void
    {
    }
}
