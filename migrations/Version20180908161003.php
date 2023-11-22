<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180908161003 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('CREATE TABLE block_user (block_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_4BFD8432E9ED820C (block_id), INDEX IDX_4BFD8432A76ED395 (user_id), PRIMARY KEY(block_id, user_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE block_user ADD CONSTRAINT FK_4BFD8432E9ED820C FOREIGN KEY (block_id) REFERENCES block (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE block_user ADD CONSTRAINT FK_4BFD8432A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE block DROP FOREIGN KEY FK_831B9722ADEC45C7');
        $this->addSql('DROP INDEX IDX_831B9722ADEC45C7 ON block');
        $this->addSql('ALTER TABLE block DROP lector_id');
    }

    public function down(Schema $schema): void
    {
    }
}
