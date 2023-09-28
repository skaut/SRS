<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180908154937 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('CREATE TABLE user_block (user_id INT NOT NULL, block_id INT NOT NULL, INDEX IDX_61D96C7AA76ED395 (user_id), INDEX IDX_61D96C7AE9ED820C (block_id), PRIMARY KEY(user_id, block_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_block ADD CONSTRAINT FK_61D96C7AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_block ADD CONSTRAINT FK_61D96C7AE9ED820C FOREIGN KEY (block_id) REFERENCES block (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
    }
}
