<?php
/**
 * Date: 7.2.13
 * Time: 17:31
 * Author: Michal Májský
 */

namespace SRS\Factory;

/**
* Pro vytvareni testovacich a inicializacnich dat submodulu Page
*/
class PageFactory
{
    public static function createInitial($em)
    {
        $homepage = new \SRS\Model\CMS\Page('Homepage', '/');
        $homepage->position = 0;
        $homepage->public = true;

        $roles = $em->getRepository('\SRS\Model\Acl\Role')->findAll();
        //stranka je verejna pro vsechny
        foreach ($roles as $role) {
            $homepage->roles->add($role);
        }
        $textContent = new \SRS\Model\CMS\TextContent();
        $textContent->page = $homepage;
        $textContent->position = 0;
        $textContent->area = 'main';
        $textContent->text = "<h2>Úspěšně jste nainstalovali SRS. Gratulujeme!</h2>";
        $textContent->text .= "<p>Obsah této stránky můžeme změnit v administraci v sekci CMS</p>";
        $em->persist($textContent);
        $em->persist($homepage);
    }

}
