<?php
/**
 * Date: 3.2.13
 * Time: 12:11
 * Author: Michal Májský
 */
namespace SRS;
class ProgramFactory
{

    public static function createBlockDataForTests(\Doctrine\ORM\EntityManager $em)
    {
        $lectorRole = $em->getRepository('\SRS\Model\Acl\Role')->findByName('Lektor');
        $lectorRole = $lectorRole[0];

        $lector1 = \SRS\Factory\UserFactory::createRandom(8888, 9999, array($lectorRole));

//        $lector1 = new \SRS\Model\User('TestLektor1');
        $lector1->firstName = 'Adam';
        $lector1->lastName = 'Novák';
        $lector1->email = 'adam@novaktest.cz';
        $lector1->role = $lectorRole;
        $lector1->nickName = 'Huhel';
        $lector1->sex = 'male';
        $lector1->about = 'lorem ipsum dolor sit amet. Hello my Name is Adam And I like food. :)';
        $lector1->displayName = $lector1->firstName . ' ' . $lector1->lastName;
//        $lector1->birthdate = new \DateTime('now');
        $lector1->approved = 1;
//        $lector1->skautISUserId = 8888;
//        $lector1->skautISPersonId = 9999;

        $lector2 = \SRS\Factory\UserFactory::createRandom(8887, 9998, array($lectorRole));
        // $lector2 = new \SRS\Model\User('TestLektorka2');
        $lector2->firstName = 'Iva';
        $lector2->lastName = 'Brázdová';
        $lector2->email = 'iva@brazdatest.cz';
        $lector2->role = $lectorRole;
        $lector2->nickName = 'Brázda';
        $lector2->sex = 'female';
        $lector2->displayName = $lector2->firstName . ' ' . $lector2->lastName;
        //$lector2->birthdate = new \DateTime('now');
        $lector2->approved = 1;
        $lector2->skautISUserId = -8887;
        $lector2->skautISPersonId = -9998;

        $em->persist($lector1);
        $em->persist($lector2);

        for ($i = 0; $i < 20; $i++) {
            $block{$i} = new \SRS\Model\Program\Block();
            $block{$i}->name = "Blok{$i}";
            $block{$i}->capacity = $i * 10;
            $block{$i}->duration = $i % 3 == 0 ? 1 : $i % 3;
            if ($i % 3 == 0) {
                $block{$i}->lector = $lector1;
            } else if ($i % 5 == 0) {
                $block{$i}->lector = $lector2;
            }
            $em->persist($block{$i});
            //\Nette\Diagnostics\Debugger::dump($block{$i});
        }
        $em->flush();
    }

}
