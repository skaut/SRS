<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210203221708 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE slideshow_content (id INT NOT NULL, images LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE slideshow_content ADD CONSTRAINT FK_3001DAD3BF396750 FOREIGN KEY (id) REFERENCES content (id) ON DELETE CASCADE');
        $this->addSql('UPDATE `settings` SET value=concat("/files/logo/", value) WHERE item="logo"');
        $this->addSql('UPDATE `user` SET photo=concat("/files/user_photos/", photo) WHERE photo IS NOT NULL');
        $this->addSql('UPDATE `custom_file_value` SET value=concat("/files", value) WHERE value IS NOT NULL');
        $this->addSql('UPDATE `document` SET file=concat("/files", file) WHERE file IS NOT NULL');
        $this->addSql('UPDATE `image_content` SET image=concat("/files", image) WHERE image IS NOT NULL');
    }

    public function down(Schema $schema): void
    {
    }
}
