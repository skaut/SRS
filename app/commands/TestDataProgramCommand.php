<?php
/**
 * Date: 14.11.12
 * Time: 20:47
 * Author: Michal Májský
 */

namespace SRS\Command;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Vlozi testovaci data pro program
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
    public function __construct(\Doctrine\ORM\EntityManager $em)
    {
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
        \SRS\Factory\ProgramFactory::createBlockDataForTests($this->em);
        $output->writeln('Testovaci Programy a bloky vlozeny');
    }
}