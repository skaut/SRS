<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 15.11.12
 * Time: 14:06
 * To change this template use File | Settings | File Templates.
 */

namespace SRS\Command;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use SRS\Factory\AclFactory;

/**
 * Inicializacni data pro Role
 */
class InitialDataRoleCommand extends Command
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
        $this->setName('srs:initial-data:acl');
        $this->setDescription('Vloží základní role a práva do DB');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $roles = AclFactory::createRoles();
        foreach ($roles as $role) {
            $this->em->persist($role);
        }
        $this->em->flush();
        $output->writeln('Role uspesne vlozeny');

    }
}