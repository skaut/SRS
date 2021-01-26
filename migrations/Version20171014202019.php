<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171014202019 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('UPDATE `mail_template_variable` SET `name` = \'application_maturity\' WHERE `mail_template_variable`.`id` = 5');
        $this->addSql('INSERT INTO `mail_template_variable` (`id`, `name`) VALUES (\'8\', \'application_subevents\'), (\'9\', \'application_fee\'), (\'10\', \'application_variable_symbol\'), (\'11\', \'bank_account\')');
        $this->addSql('INSERT INTO `template_template_variable` (`template_id`, `template_variable_id`) VALUES (\'2\', \'5\'), (\'2\', \'9\'), (\'2\', \'11\'), (\'2\', \'10\')');
        $this->addSql('INSERT INTO `template_template_variable` (`template_id`, `template_variable_id`) VALUES (\'6\', \'8\')');
    }

    public function down(Schema $schema): void
    {
    }
}
