<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221101001721 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('INSERT INTO `mail_template` (`id`, `type`, `subject`, `text`, `active`, `system_template`) VALUES (NULL, \'troop_registration\', \'Registrace na akci NSJ2023!\', \'Ahoj, děkujeme za registraci na NSJ2023. Tento e-mail je potvrzením, že registrace tvé skupiny byla řádně uložena do systému. Abychom s vámi mohli počítat je nutné nejpozději do 30 kalendářních dnů, nejpozději však do 15. února 2023 (pokud by nastalo dříve) uhradit %poplatek% Kč účastnického poplatku za celou skupinu na účet: %cislo-uctu% s variabilním symbolem: %variabilni-symbol%. S platbou prosím neotálej. Svou skupinu můžeš spravovat po přihlášení na nsj2023.cz. Pokud by se vyskytly jakékoliv potíže, ozvi se nám na registrace@nsj2023.cz. Těšíme se! Tým NSJ2023\', \'1\', \'0\')');

        $this->addSql('INSERT INTO `template_template_variable` (`template_id`, `template_variable_id`) VALUES (\'15\', \'1\')');
        $this->addSql('INSERT INTO `template_template_variable` (`template_id`, `template_variable_id`) VALUES (\'15\', \'5\')');
        $this->addSql('INSERT INTO `template_template_variable` (`template_id`, `template_variable_id`) VALUES (\'15\', \'9\')');
        $this->addSql('INSERT INTO `template_template_variable` (`template_id`, `template_variable_id`) VALUES (\'15\', \'10\')');
        $this->addSql('INSERT INTO `template_template_variable` (`template_id`, `template_variable_id`) VALUES (\'15\', \'11\')');
    }

    public function down(Schema $schema): void
    {
    }
}
