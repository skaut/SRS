<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 30.10.12
 * Time: 21:16
 * To change this template use File | Settings | File Templates.
 */
namespace BackModule;
class AclPresenter extends BasePresenter
{
    protected function createComponentUserGrid()
    {
        return new \SRS\Components\UserGrid($this->context->database);
    }

    public function renderList() {

    }
}
