<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
/**
 * Auto-generated Migration: Please modify to your needs!
 */

final class Version20181013201838 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('ALTER TABLE payment CHANGE ammount amount DOUBLE PRECISION NOT NULL');
        $this->addSql('INSERT INTO `settings` (`item`, `value`) VALUES (\'bank_download_from\', NULL)');
    }

    public function down(Schema $schema): void
    {
    }
}
