<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230211081334 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DELETE FROM `settings` WHERE `item`=\'ga_id\'');
        $this->addSql('INSERT INTO `settings` (`item`, `value`) VALUES (\'tracking_code\', NULL)');
    }

    public function down(Schema $schema) : void
    {
    }
}
