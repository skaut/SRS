<?php

namespace App\Commands;

use App\Commands\Fixtures\CMSFixture;
use App\Commands\Fixtures\PermissionFixture;
use App\Commands\Fixtures\ResourceFixture;
use App\Commands\Fixtures\RoleFixture;
use App\Commands\Fixtures\SettingsFixture;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FixturesLoadCommand extends Command
{
    /**
     * @var \Kdyby\Doctrine\EntityManager
     * @inject
     */
    public $em;

    /**
     * @var \Kdyby\Translation\Translator
     * @inject
     */
    public $translator;

    protected function configure()
    {
        $this->setName('app:fixtures:load');
        $this->setDescription('Loads initial data to database.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $fixtures = [];
            $fixtures[] = new SettingsFixture($this->translator);
            $fixtures[] = new ResourceFixture();
            $fixtures[] = new PermissionFixture();
            $fixtures[] = new RoleFixture($this->translator);
            $fixtures[] = new CMSFixture($this->translator);

            $purger = new ORMPurger($this->em);
            $executor = new ORMExecutor($this->em, $purger);
            $executor->execute($fixtures);
            return 0;
        } catch (\Exception $exc) {
            $output->write("error");
            return 1;
        }
    }
}