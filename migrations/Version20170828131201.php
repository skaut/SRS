<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170828131201 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('INSERT INTO `resource` (`id`, `name`) VALUES (8, \'structure\');');
        $this->addSql('INSERT INTO `permission` (`id`, `resource_id`, `name`) VALUES (14, 8, \'manage\');');
        $this->addSql('INSERT INTO `role_permission` (`role_id`, `permission_id`) VALUES (7, 14), (8, 14);');
    }

    public function down(Schema $schema): void
    {
    }
}
