<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200523200237 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE custom_input_role (custom_input_id INT NOT NULL, role_id INT NOT NULL, INDEX IDX_2BFB0DF7D390FDB2 (custom_input_id), INDEX IDX_2BFB0DF7D60322AC (role_id), PRIMARY KEY(custom_input_id, role_id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE custom_date (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE custom_datetime (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE custom_multiselect (id INT NOT NULL, options LONGTEXT NOT NULL COMMENT \'(DC2Type:simple_array)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE custom_datetime_value (id INT NOT NULL, value DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE custom_date_value (id INT NOT NULL, value DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE custom_multiselect_value (id INT NOT NULL, value LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE custom_input_role ADD CONSTRAINT FK_2BFB0DF7D390FDB2 FOREIGN KEY (custom_input_id) REFERENCES custom_input (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE custom_input_role ADD CONSTRAINT FK_2BFB0DF7D60322AC FOREIGN KEY (role_id) REFERENCES role (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE custom_date ADD CONSTRAINT FK_ABE8FF87BF396750 FOREIGN KEY (id) REFERENCES custom_input (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE custom_datetime ADD CONSTRAINT FK_762121AEBF396750 FOREIGN KEY (id) REFERENCES custom_input (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE custom_multiselect ADD CONSTRAINT FK_A0C83E4ABF396750 FOREIGN KEY (id) REFERENCES custom_input (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE custom_datetime_value ADD CONSTRAINT FK_A1E3BBF396750 FOREIGN KEY (id) REFERENCES custom_input_value (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE custom_date_value ADD CONSTRAINT FK_98788C12BF396750 FOREIGN KEY (id) REFERENCES custom_input_value (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE custom_multiselect_value ADD CONSTRAINT FK_4D1C00CCBF396750 FOREIGN KEY (id) REFERENCES custom_input_value (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE role DROP display_arrival_departure');
        $this->addSql('ALTER TABLE custom_select CHANGE options options LONGTEXT NOT NULL COMMENT \'(DC2Type:simple_array)\'');
        $this->addSql('ALTER TABLE user DROP arrival, DROP departure');
    }

    public function down(Schema $schema): void
    {
    }
}
