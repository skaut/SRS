<?php

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Nette\Security\Passwords;

class CMSFixture extends AbstractFixture
{
    public function load(ObjectManager $manager)
    {
        $homepage = new

        $manager->persist($demo);

        $manager->flush();
    }
}