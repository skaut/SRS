<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */

class Version20171224074439 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('ALTER TABLE mail_template ADD `system` TINYINT(1) NOT NULL');
        $this->addSql('UPDATE `mail_template` SET `send_to_user` = 0, `system` = 1 WHERE `mail_template`.`id` = 10');
    }

    public function down(Schema $schema): void
    {
    }
}
