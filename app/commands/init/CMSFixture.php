<?php

namespace App\Commands\Init;

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
        $homepage->position = 0;
        $homepage->public = true;

        foreach (Role::$roles as $role) {
            $homepage->roles->add($this->getReference($role));
        }

        $manager->persist($homepage);
        $this->addReference('homepage', $homepage);

        $textContent = new TextContent();
        $textContent->page = $this->getReference('homepage');
        $textContent->position = 0;
        $textContent->area = Content::MAIN;
        $textContent->text = "<h2>Úspěšně jste nainstalovali SRS. Gratulujeme!</h2>";
        $textContent->text = "<p>Obsah této stránky můžeme změnit v administraci v sekci CMS.</p>";

        $manager->persist($textContent);
        $manager->flush();
    }

    /**
     * @return array
     */
    function getDependencies()
    {
        return array('RoleFixture');
    }
}