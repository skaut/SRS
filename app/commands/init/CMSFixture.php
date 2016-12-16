<?php

namespace App\Commands;

use App\Model\ACL\Role;
use App\Model\CMS\Content\Content;
use App\Model\CMS\Content\TextContent;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use App\Model\CMS\Page;

class CMSFixture extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $homepage = new Page('Homepage', '/');
        $homepage->setPosition(0);
        $homepage->setPublic(true);

        foreach (Role::$roles as $role) {
            $homepage->addRole($this->getReference($role));
        }

        $manager->persist($homepage);
        $this->addReference('homepage', $homepage);

        $textContent = new TextContent(null, $this->getReference('homepage'), Content::MAIN, 0,
            "<h2>Úspěšně jste nainstalovali SRS. Gratulujeme!</h2>" .
            "<p>Obsah této stránky můžeme změnit v administraci v sekci CMS.</p>"
        );

        $manager->persist($textContent);
        $manager->flush();
    }

    /**
     * @return array
     */
    function getDependencies()
    {
        return array('App\Commands\RoleFixture');
    }
}