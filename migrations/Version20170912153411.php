<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20170912153411 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE role CHANGE fee fee INT DEFAULT NULL');
        $this->addSql('ALTER TABLE application ADD fee INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
    }
}
