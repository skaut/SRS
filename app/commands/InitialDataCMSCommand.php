<?php
/**
 * Date: 15.11.12
 * Time: 14:06
 * Author: Michal Májský
 */

namespace SRS\Command;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use SRS\Factory\PageFactory;

/**
 * Inicializacni data pro CMS
 */
class InitialDataCMSCommand extends Command
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
        $this->setName('srs:initial-data:cms');
        $this->setDescription('Vloží základní role a práva do DB');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        PageFactory::createInitial($this->em);

        $this->em->flush();
        $output->writeln('CMS data uspesne vlozena');

    }
}