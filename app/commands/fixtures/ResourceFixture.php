<?php

namespace App\Commands\Fixtures;


use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use App\Model\ACL\Resource;

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
            $this->addReference("resource_" . $key, $value);
        }
    }
}