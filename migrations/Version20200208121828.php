<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200208121828 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE role CHANGE registerable_from registerable_from DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE registerable_to registerable_to DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE document CHANGE timestamp timestamp DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE news CHANGE published published DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE mail CHANGE datetime datetime DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE payment CHANGE date date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\'');
        $this->addSql('ALTER TABLE program CHANGE start start DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE subevent ADD registerable_from DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD registerable_to DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE application CHANGE application_date application_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE payment_date payment_date DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE income_proof_printed_date income_proof_printed_date DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE maturity_date maturity_date DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE valid_from valid_from DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE valid_to valid_to DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE user CHANGE birthdate birthdate DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE last_login last_login DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE arrival arrival DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE departure departure DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE photo_update photo_update DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE roles_application_date roles_application_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE last_payment_date last_payment_date DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\'');
    }

    public function down(Schema $schema): void
    {
    }
}
