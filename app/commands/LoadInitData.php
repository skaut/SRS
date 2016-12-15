<?php

namespace App\Commands;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LoadInitData extends Command
{
    /**
     * @var \Kdyby\Doctrine\EntityManager
     * @inject
     */
    public $em;

    protected function configure()
    {
        $this->setName('orm:init-data:load');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        try {
            $loader = new Loader();
            $loader->loadFromDirectory(__DIR__ . '/commands/init');
            $fixtures = $loader->getFixtures();

            $purger = new ORMPurger($this->em);

            $executor = new ORMExecutor($this->em, $purger);
            $executor->execute($fixtures);
            return 0;
        } catch (\Exception $exc) {
            return 1;
        }
    }
}