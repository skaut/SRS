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
use SRS\Factory\UserFactory;

/**
 * Slouzi pro vlozeni testovacich uzivatelu
 */
class TestDataUsersCommand extends Command
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
        $this->setName('srs:test-data:user');
        $this->setDescription('Vloží do databáze testovací uživatele');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $query = $this->em->createQuery('SELECT min(u.skautISUserId), min(u.skautISPersonId) FROM \SRS\Model\User u');
        $result = $query->getSingleResult();

        $minUserId = $result[1] ? $result[1] : -1;
        $minPersonId = $result[2] ? $result[2] : -1;
        $roles = $this->em->getRepository('\SRS\Model\Role')->findAll();
      //  $roles->toArray();
        for($i = 0; $i < 20; $i++) {
            $user = UserFactory::createRandom(--$minUserId, --$minPersonId, $roles);
            $this->em->persist($user);
        }
        $this->em->flush();
        $output->writeln('Testovaci uzivatele uspesne vlozeni do databaze');
    }
}