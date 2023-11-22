<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171001080451 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE custom_select_value (id INT NOT NULL, value INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE custom_select_value ADD CONSTRAINT FK_CFE4DE74BF396750 FOREIGN KEY (id) REFERENCES custom_input_value (id) ON DELETE CASCADE');
        $this->addSql('INSERT INTO `settings` (`item`, `value`) SELECT \'edit_custom_inputs_to\', `value` FROM `settings` WHERE `item` = \'edit_registration_to\'');
    }

    public function down(Schema $schema): void
    {
    }
}
