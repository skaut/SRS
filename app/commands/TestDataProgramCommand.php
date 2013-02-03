<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 14.11.12
 * Time: 20:47
 * To change this template use File | Settings | File Templates.
 */

namespace SRS\Command;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Slouzi pro vlozeni testovacich uzivatelu
 */
class TestDataProgramCommand extends Command
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @param \Doctrine\ORM\EntityManager
     */
    public function __construct(\Doctrine\ORM\EntityManager $em) {
        parent::__construct();
        $this->em = $em;
    }

    protected function configure()
    {
        $this->setName('srs:test-data:program');
        $this->setDescription('Vloží do databáze testovací Bloky a programy');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
       \ProgramFactory::createBlockDataForTests($this->em);
        $output->writeln('Testovaci Programy a bloky vlozeny');
    }
}