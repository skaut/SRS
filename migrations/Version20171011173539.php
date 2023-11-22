<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171011173539 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE application ADD cancel_date DATE DEFAULT NULL');
        $this->addSql('DELETE FROM `settings` WHERE `settings`.`item` = \'is_allowed_register_programs\'');
        $this->addSql('INSERT INTO `settings` (`item`, `value`) VALUES (\'register_programs_type\', \'allowed\'), (\'cancel_registration_after_maturity\', \'3\')');
    }

    public function down(Schema $schema): void
    {
    }
}
