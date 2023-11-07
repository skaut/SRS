<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20171228185809 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('CREATE TABLE custom_file (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE custom_file_value (id INT NOT NULL, value VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE custom_file ADD CONSTRAINT FK_8DE9FEEDBF396750 FOREIGN KEY (id) REFERENCES custom_input (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE custom_file_value ADD CONSTRAINT FK_880BD4A9BF396750 FOREIGN KEY (id) REFERENCES custom_input_value (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
    }
}
