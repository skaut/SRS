<?php
declare(strict_types=1);

namespace Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170828131201 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('INSERT INTO `resource` (`id`, `name`) VALUES (8, \'structure\');');
        $this->addSql('INSERT INTO `permission` (`id`, `resource_id`, `name`) VALUES (14, 8, \'manage\');');
        $this->addSql('INSERT INTO `role_permission` (`role_id`, `permission_id`) VALUES (7, 14), (8, 14);');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
    }
}
