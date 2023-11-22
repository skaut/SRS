<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231122111336 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE `group` (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, leader_id INT NOT NULL, leader_email VARCHAR(255) NOT NULL, create_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', group_status_id INT NOT NULL, places VARCHAR(255) NOT NULL, price INT NOT NULL, note LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE status (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_7B00651C5E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('INSERT INTO `settings` (`item`, `value`) VALUES (\'group_fill_term\', \'2023-10-20\')');
        $this->addSql('INSERT INTO `settings` (`item`, `value`) VALUES (\'group_max_members\', \'11\')');
        $this->addSql('INSERT INTO `settings` (`item`, `value`) VALUES (\'group_min_members\', \'5\')');
        $this->addSql('INSERT INTO `resource` (`id`,`name`) VALUES (9, \'groups\')');
        $this->addSql('INSERT INTO `permission` (`id`, `resource_id`, `name`) VALUES (15, 9, \'manage\')');
        $this->addSql('INSERT INTO `role_permission` (`role_id`, `permission_id`) VALUES (8, 15)');
        $this->addSql('INSERT INTO `role_permission` (`role_id`, `permission_id`) VALUES (7, 15)');
        $this->addSql('INSERT INTO `status` (`name`) VALUES (\'čeká na naplnění\')');
        $this->addSql('INSERT INTO `status` (`name`) VALUES (\'čeká na zaplacení\')');
        $this->addSql('INSERT INTO `status` (`name`) VALUES (\'zaplacená\')');
        $this->addSql('INSERT INTO `status` (`name`) VALUES (\'zrušená\')');
    }

    public function down(Schema $schema): void
    {
    }
}
