<?php

namespace App\Commands\Fixtures;

use App\Model\ACL\Resource;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;


/**
 * Vytváří prostředky.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ResourceFixture extends AbstractFixture
{
    /**
     * Vytváří počáteční data.
     * @param ObjectManager $manager
     */
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