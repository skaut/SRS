<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 20.12.12
 * Time: 0:49
 * To change this template use File | Settings | File Templates.
 */
namespace SRS\Model\CMS;

interface IContent
{
    /**
     * @param Nette\Application\UI\Form $form
     * @return void
     */
    public function setValuesFromPageForm(\Nette\Application\UI\Form $form);

    /**
     * @param Nette\Application\UI\Form $form
     * @return \Nette\Application\UI\Form $form
     */
    public function addFormItems(\Nette\Application\UI\Form $form);

}
