<?php

namespace App\Commands;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitDataCommand extends Command
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

    /**
     * @var \App\ConfigFacade
     * @inject
     */
    public $configFacade;

    protected function configure()
    {
        $this->setName('app:init-data:load');
        $this->setDescription('Loads initial data to database.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        try {
            $fixtures = array();
            $fixtures[] = new SettingsFixture($this->translator, $this->configFacade);
            $fixtures[] = new ResourceFixture();
            $fixtures[] = new PermissionFixture();
            $fixtures[] = new RoleFixture();
            $fixtures[] = new CMSFixture();

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