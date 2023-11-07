<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231025195652 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DELETE FROM `settings` WHERE `item`=\'seminar_email\'');
        $this->addSql('DELETE FROM `settings` WHERE `item`=\'seminar_email_unverified\'');
        $this->addSql('DELETE FROM `settings` WHERE `item`=\'seminar_email_verification_code\'');
    }

    public function down(Schema $schema): void
    {
    }
}
