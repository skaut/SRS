<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20170919204840 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DELETE FROM `role_permission` WHERE `role_permission`.`role_id` = 7 AND `role_permission`.`permission_id` = 14');
        $this->addSql('DELETE FROM `role_permission` WHERE `role_permission`.`role_id` = 8 AND `role_permission`.`permission_id` = 14');
        $this->addSql('DELETE FROM `permission` WHERE `permission`.`id` = 14');
        $this->addSql('DELETE FROM `resource` WHERE `resource`.`id` = 8');
    }

    public function down(Schema $schema): void
    {
    }
}
