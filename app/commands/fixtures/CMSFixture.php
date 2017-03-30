<?php

namespace App\Commands\Fixtures;

use App\Model\ACL\Role;
use App\Model\CMS\Content\Content;
use App\Model\CMS\Content\TextContent;
use App\Model\CMS\Page;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Kdyby\Translation\Translator;


/**
 * Vytváří počáteční úvodní stránku.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class CMSFixture extends AbstractFixture implements DependentFixtureInterface
{
    /** @var Translator */
    protected $translator;


    /**
     * CMSFixture constructor.
     * @param Translator $translator
     */
    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Vytváří počáteční data.
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $homepage = new Page($this->translator->translate('common.cms.default.homepage_name'), '/');
        $homepage->setPosition(1);
        $homepage->setPublic(TRUE);

        foreach (Role::$roles as $role) {
            $homepage->addRole($this->getReference($role));
        }

        $manager->persist($homepage);
        $this->addReference('homepage', $homepage);

        $textContent = new TextContent($this->getReference('homepage'), Content::MAIN);
        $textContent->setPosition(1);
        $textContent->setHeading($this->translator->translate('common.cms.default.homepage_heading'));
        $textContent->setText($this->translator->translate('common.cms.default.homepage_text'));

        $manager->persist($textContent);
        $manager->flush();
    }

    /**
     * Vrací závislosti na jiných fixtures.
     * @return array
     */
    public function getDependencies()
    {
        return ['App\Commands\Fixtures\RoleFixture'];
    }
}
