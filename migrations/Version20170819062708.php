<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170819062708 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE template_template_variable (template_id INT NOT NULL, template_variable_id INT NOT NULL, INDEX IDX_62257D3C5DA0FB8 (template_id), INDEX IDX_62257D3CF8FA6AEA (template_variable_id), PRIMARY KEY(template_id, template_variable_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE mail_template_variable (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE template_template_variable ADD CONSTRAINT FK_62257D3C5DA0FB8 FOREIGN KEY (template_id) REFERENCES mail_template (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE template_template_variable ADD CONSTRAINT FK_62257D3CF8FA6AEA FOREIGN KEY (template_variable_id) REFERENCES mail_template_variable (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
    }
}
