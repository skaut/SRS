<?php

namespace Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170823134902 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('INSERT INTO `settings` (`item`, `value`) VALUES 
                             (\'seminar_email_unverified\', NULL),
                             (\'seminar_email_verification_code\', NULL)');

        $this->addSql('INSERT INTO `mail_template_variable` (`id`, `name`) VALUES (NULL, \'email_verification_link\')');

        $this->addSql('INSERT INTO `mail_template` (`id`, `type`, `subject`, `text`, `active`) VALUES (NULL, \'email_verification\', \'%nazev-seminare%: potvrzení změny e-mailu\', \'Změnu e-mailu semináře potvrdíš otevřením následujícho odkazu: <a href="%overovaci-odkaz%">%overovaci-odkaz%</a>.\', \'1\')');

        $this->addSql('INSERT INTO `template_template_variable` (`template_id`, `template_variable_id`) VALUES (\'10\', \'1\')');
        $this->addSql('INSERT INTO `template_template_variable` (`template_id`, `template_variable_id`) VALUES (\'10\', \'7\')');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
