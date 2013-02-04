<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 4.2.13
 * Time: 15:15
 * To change this template use File | Settings | File Templates.
 */

namespace SRS\Components;
/**
 * Slouzi pro vyber programu na frontendu
 */
class ProgramBox extends \Nette\Application\UI\Control
{

    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/template.latte');

        $template->render();
    }

}
