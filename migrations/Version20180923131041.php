<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20180923131041 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('ALTER TABLE user ADD external_lector TINYINT(1) NOT NULL, ADD fee INT DEFAULT NULL, ADD fee_remaining INT DEFAULT NULL, ADD payment_method VARCHAR(255) DEFAULT NULL, ADD last_payment_date DATE DEFAULT NULL, ADD not_registered_mandatory_blocks_count INT DEFAULT NULL, DROP membership_type, DROP membership_category, CHANGE first_login roles_application_date DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
    }
}
