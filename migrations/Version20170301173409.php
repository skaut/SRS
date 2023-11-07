<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

use function file_get_contents;
use function preg_split;
use function trim;

class Version20170301173409 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $dump = file_get_contents(__DIR__ . '/initial_schema.sql');

        $statement = '';
        foreach (preg_split('/\r\n|\r|\n/', $dump) as $row) {
            if ($row === '') {
                $this->addSql(trim($statement));
                $statement = '';
            } else {
                $statement .= ' ' . trim($row);
            }
        }
    }

    public function down(Schema $schema): void
    {
    }
}
