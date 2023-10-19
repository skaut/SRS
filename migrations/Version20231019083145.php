<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231019083145 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mail CHANGE recipient_emails recipient_emails longtext COLLATE \'utf8_unicode_ci\' NULL');
    }

    public function down(Schema $schema): void
    {
    }
}
