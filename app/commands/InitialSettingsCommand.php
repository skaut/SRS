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
use SRS\Factory\SettingsFactory;

/**
 * Inicializacni data pro Settings
 */
class InitialSettingsCommand extends Command
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
        $this->setName('srs:initial-data:settings');
        $this->setDescription('Vloží základní položky settings do DB');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $settings = SettingsFactory::create();
        foreach ($settings as $item) {
            $this->em->persist($item);
        }
        $this->em->flush();
        $output->writeln('Inicializacni data pro Settings uspesne vlozeny');

    }
}