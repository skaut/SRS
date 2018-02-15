<?php declare(strict_types = 1);

namespace Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180215123647 extends AbstractMigration
{
    public function up(Schema $schema)
    {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('RENAME TABLE tag TO category_document');
		$this->addSql('CREATE TABLE document_content_category_document (document_content_id INT NOT NULL, category_document_id INT NOT NULL, INDEX IDX_D22A1D5E256308BB (document_content_id), INDEX IDX_D22A1D5E74A43A5C (category_document_id), PRIMARY KEY(document_content_id, category_document_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
		$this->addSql('CREATE TABLE document_category_document (document_id INT NOT NULL, category_document_id INT NOT NULL, INDEX IDX_7AFDFC6C33F7837 (document_id), INDEX IDX_7AFDFC674A43A5C (category_document_id), PRIMARY KEY(document_id, category_document_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
		$this->addSql('ALTER TABLE document_content_category_document ADD CONSTRAINT FK_D22A1D5E256308BB FOREIGN KEY (document_content_id) REFERENCES document_content (id) ON DELETE CASCADE');
		$this->addSql('ALTER TABLE document_content_category_document ADD CONSTRAINT FK_D22A1D5E74A43A5C FOREIGN KEY (category_document_id) REFERENCES category_document (id) ON DELETE CASCADE');
		$this->addSql('ALTER TABLE document_category_document ADD CONSTRAINT FK_7AFDFC6C33F7837 FOREIGN KEY (document_id) REFERENCES document (id) ON DELETE CASCADE');
		$this->addSql('ALTER TABLE document_category_document ADD CONSTRAINT FK_7AFDFC674A43A5C FOREIGN KEY (category_document_id) REFERENCES category_document (id) ON DELETE CASCADE');
		$this->addSql('DROP INDEX uniq_389b7835e237e06 ON category_document');
		$this->addSql('CREATE UNIQUE INDEX UNIQ_6F130E0D5E237E06 ON category_document (name)');
	}

    public function down(Schema $schema)
    {
    }
}
