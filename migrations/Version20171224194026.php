<?php declare(strict_types = 1);

namespace Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171224194026 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE application ADD variable_symbol_id INT DEFAULT NULL, DROP variable_symbol');
        $this->addSql('ALTER TABLE application ADD CONSTRAINT FK_A45BDDC125813A9D FOREIGN KEY (variable_symbol_id) REFERENCES variable_symbol (id)');
        $this->addSql('CREATE INDEX IDX_A45BDDC125813A9D ON application (variable_symbol_id)');
    }

    public function down(Schema $schema)
    {
    }
}
