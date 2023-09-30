<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20170828220102 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE discount CHANGE operator condition_operator VARCHAR(255) NOT NULL');
        $this->addSql('INSERT INTO `subevent` (`id`, `name`, `implicit`, `fee`, `capacity`) VALUES (NULL, (SELECT `value` FROM `settings` WHERE `item` = \'seminar_name\'), \'1\', \'0\', NULL)');
    }

    public function down(Schema $schema): void
    {
    }
}
