<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20221022182325 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE patrol ADD confirmed TINYINT(1) NOT NULL');
        $this->addSql('UPDATE role SET type=\'individual\', maximum_age=150');
        $this->addSql('UPDATE role SET type=\'patrol\', minimum_age=10, maximum_age=16 WHERE name=\'Účastník\'');
        $this->addSql('INSERT INTO `role` (`name`, `system_role`, `registerable`, `approved_after_registration`, `synced_with_skaut_is`, `occupancy`, `minimum_age`, `type`, `maximum_age`) VALUES (\'Rádce\', 1, 1, 1, 0, 0, 10, \'patrol\', 17)');
        $this->addSql('INSERT INTO `role` (`name`, `system_role`, `registerable`, `approved_after_registration`, `synced_with_skaut_is`, `occupancy`, `minimum_age`, `type`, `maximum_age`) VALUES (\'Vedoucí\', 1, 1, 1, 0, 0, 18, \'patrol\', 150)');
        $this->addSql('INSERT INTO `role` (`name`, `system_role`, `registerable`, `approved_after_registration`, `synced_with_skaut_is`, `occupancy`, `minimum_age`, `type`, `maximum_age`) VALUES (\'Dospělý doprovod\', 1, 1, 1, 0, 0, 18, \'troop\', 150)');
    }

    public function down(Schema $schema): void
    {
    }
}
