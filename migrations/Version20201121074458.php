<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201121074458 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('INSERT INTO `mail_template` (`id`, `type`, `subject`, `text`, `active`) VALUES (NULL, \'registration_canceled_not_paid\', \'%nazev-seminare%: odhlášení ze semináře (nezaplaceno)\', \'Tvoje účast na akci %nazev-seminare% byla zrušena z důvodu nezaplacení. Pokud jde o omyl, tak se znovu zaregistruj na webu.\', \'1\')');
        $this->addSql('INSERT INTO `mail_template` (`id`, `type`, `subject`, `text`, `active`) VALUES (NULL, \'registration_approved\', \'%nazev-seminare%: schválení registrace\', \'Tvoje registrace na akci %nazev-seminare% byla schválena pořadatelem. Nyní se můžeš přihlásit na webu.\', \'1\')');
        $this->addSql('INSERT INTO `template_template_variable` (`template_id`, `template_variable_id`) VALUES (\'13\', \'1\')');
        $this->addSql('INSERT INTO `template_template_variable` (`template_id`, `template_variable_id`) VALUES (\'14\', \'1\')');
    }

    public function down(Schema $schema) : void
    {
    }
}
