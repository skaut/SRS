<?php

namespace App\Commands\Fixtures;


use App\Model\ACL\Resource;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

class ResourceFixture extends AbstractFixture
{

    public function load(ObjectManager $manager)
    {
        $resources = [];
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