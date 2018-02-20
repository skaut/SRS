<?php declare(strict_types = 1);
namespace Migrations;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180220173247 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('CREATE TABLE tag_role (tag_id INT NOT NULL, role_id INT NOT NULL, INDEX IDX_B96635DCBAD26311 (tag_id), INDEX IDX_B96635DCD60322AC (role_id), PRIMARY KEY(tag_id, role_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tag_role ADD CONSTRAINT FK_B96635DCBAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tag_role ADD CONSTRAINT FK_B96635DCD60322AC FOREIGN KEY (role_id) REFERENCES role (id) ON DELETE CASCADE');
    }
    public function down(Schema $schema)
    {
	}
}