<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221126184510 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('INSERT INTO `mail_template` (`id`, `type`, `subject`, `text`, `active`, `system_template`) VALUES (16, \'troop_payment_confirmed\', \'Potvrzení platby akce NSJ2023!\', \'Ahoj, potvrzujeme přijetí platby za tvou skupinu na NSJ2023. Potvrzení platby si můžeš stáhnout po přihlášení.\', \'1\', \'0\')');

        $this->addSql('INSERT INTO `template_template_variable` (`template_id`, `template_variable_id`) VALUES (\'16\', \'1\')');
    }

    public function down(Schema $schema): void
    {
    }
}
