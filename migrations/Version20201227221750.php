<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201227221750 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE program_application (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, program_id INT DEFAULT NULL, alternate TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_684190A9A76ED395 (user_id), INDEX IDX_684190A93EB8070A (program_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE program_application ADD CONSTRAINT FK_684190A9A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE program_application ADD CONSTRAINT FK_684190A93EB8070A FOREIGN KEY (program_id) REFERENCES program (id)');
        $this->addSql('INSERT INTO program_application (user_id, program_id, alternate, created_at) SELECT user_id, program_id, FALSE, now() FROM user_program');
        $this->addSql('DROP TABLE user_program');
    }

    public function down(Schema $schema): void
    {
    }
}
