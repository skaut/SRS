<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 23.3.13
 * Time: 9:44
 * To change this template use File | Settings | File Templates.
 */

namespace SRS\Command;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use SRS\Factory\UserFactory;
use SRS\Model\Acl\Role;

class TestCasesDataCommand extends Command
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
        $this->setName('srs:test-data:test-cases');
        $this->setDescription('Vloží do databáze data pro uživatelské testování dle scénářů');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $query = $this->em->createQuery('SELECT min(u.skautISUserId), min(u.skautISPersonId) FROM \SRS\Model\User u');
        $result = $query->getSingleResult();

        $minUserId = $result[1] ? $result[1] : -1;
        $minPersonId = $result[2] ? $result[2] : -1;
        $roleServis = $this->em->getRepository('\SRS\Model\Acl\Role')->findOneByName(Role::SERVICE_TEAM);
        $roleOrg = $this->em->getRepository('\SRS\Model\Acl\Role')->findOneByName(Role::ORGANIZER);
        $roleLector = $this->em->getRepository('\SRS\Model\Acl\Role')->findOneByName(Role::LECTOR);
        $roleAttendee = $this->em->getRepository('\SRS\Model\Acl\Role')->findOneByName(Role::ATTENDEE);
        $roles = $this->em->getRepository('\SRS\Model\Acl\Role')->findAll();
        $settings =  $this->em->getRepository('\SRS\Model\Settings');

        $settings->set('user_custom_text_0', 'Máte nějaká stravovací omezenení?');

        $users = array();
        for ($i = 0; $i < 5; $i++) {
            $users[] = $user =  UserFactory::createRandom(--$minUserId, --$minPersonId, $roles);
            $this->em->persist($user);
        }
        $users[0]->firstName = "Martin";
        $users[0]->lastName = "Novák";
        $users[0]->displayName = "Novák Martin";
        $users[0]->nickName = "";
        $users[0]->role = $roleAttendee;
        $users[0]->username = 'marnov';

        $users[1]->firstName = "Franta";
        $users[1]->lastName = "Vomáčka";
        $users[1]->displayName = "Vomáčka Martin (Guláš)";
        $users[1]->nickName = "Guláš";
        $users[1]->role = $roleServis;
        $users[1]->username = 'vomacka378';
        $users[1]->approved = false;

        $users[2]->firstName = "Líba";
        $users[2]->lastName = "Houbová";
        $users[2]->displayName = "Houbová Líba (Edka)";
        $users[2]->nickName = "Edka";
        $users[2]->role = $roleServis;
        $users[2]->username = 'edka';
        $users[2]->approved = false;
        $users[2]->customText0 = 'Bezlepková dieta';

        $users[3]->firstName = "Zdeněk";
        $users[3]->lastName = "Bouda";
        $users[3]->displayName = "Bouda Zdeněk";
        $users[3]->nickName = "";
        $users[3]->role = $roleLector;
        $users[3]->username = 'bouda';

        $block1 = new \SRS\Model\Program\Block();
        $block1->name = "Právo pro laiky";
        $block1->capacity = 20;
        $block1->tools = 'Kniha Obchodní právo';
        $block1->lector = $users[3];
        $block1->duration = 2;
        $this->em->persist($block1);

        $block2 = new \SRS\Model\Program\Block();
        $block2->name = "Oběd";
        $block2->capacity = 20;
        $block2->tools = 'příbor';
        $block2->lector = $users[3];
        $block2->duration = 1;
        $this->em->persist($block2);


        $block3 = new \SRS\Model\Program\Block();
        $block3->name = "skautIS a novinky";
        $block3->capacity = 20;
        $block3->tools = 'přístup k internetu';
        //$block3->lector = "";
        $block3->duration = 1;
        $this->em->persist($block3);

        $block4 = new \SRS\Model\Program\Block();
        $block4->name = "Snídaně";
        $block4->capacity = 20;
        $block4->tools = 'příbor';
        $block4->lector = $users[3];
        $block4->duration = 1;
        $this->em->persist($block4);

        $block5 = new \SRS\Model\Program\Block();
        $block5->name = "Večere";
        $block5->capacity = 20;
        $block5->tools = 'příbor';
        $block5->lector = $users[3];
        $block5->duration = 1;
        $this->em->persist($block5);

        $block6 = new \SRS\Model\Program\Block();
        $block6->name = "Skauting v zahraničí";
        $block6->capacity = 20;
        $block6->tools = '';
        $block6->lector = $users[3];
        $block6->duration = 1;
        $this->em->persist($block6);




        $this->em->flush();
        $output->writeln('Data pro testovaci scenare uspesne vlozny');
    }

}
