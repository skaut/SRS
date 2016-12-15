<?php

namespace App\Commands\Init;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Nette\Security\Passwords;
use App\Model\ACL\Role;
use App\Model\ACL\Resource;
use App\Model\ACL\Permission;

class ResourceFixture extends AbstractFixture
{

    public function load(ObjectManager $manager)
    {
        $resources = array();
        foreach (Resource::$resources as $resource) {
            $resources[$resource] = new Resource($resource);
            $manager->persist($resources[$resource]);
        }
        $manager->flush();

        foreach ($resources as $key => $value) {
            $this->addReference($key, $value);
        }
    }
}