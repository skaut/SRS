<?php

namespace Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170919204840 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DELETE FROM `role_permission` WHERE `role_permission`.`role_id` = 7 AND `role_permission`.`permission_id` = 14');
        $this->addSql('DELETE FROM `role_permission` WHERE `role_permission`.`role_id` = 8 AND `role_permission`.`permission_id` = 14');
        $this->addSql('DELETE FROM `permission` WHERE `permission`.`id` = 14');
        $this->addSql('DELETE FROM `resource` WHERE `resource`.`id` = 8');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
