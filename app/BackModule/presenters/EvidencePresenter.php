<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 26.1.13
 * Time: 21:07
 * To change this template use File | Settings | File Templates.
 */

namespace BackModule;

class EvidencePresenter extends BasePresenter
{
    protected $resource = 'Evidence';

    public function startup() {
        parent::startup();
       // $this->checkPermissions('Přístup');
        //$this->blockRepo = $this->context->database->getRepository('\SRS\Model\Program\Block');
    }

    public function beforeRender() {
        parent::beforeRender();

    }

    public function renderList() {
        $dbParams = $this->getDBParams();
        $dsn = "mysql:host={$dbParams['host']};dbname={$dbParams['dbname']}";
        $netteDatabase = new \Nette\Database\Connection($dsn, $dbParams['user'], $dbParams['password']);
        $table = $netteDatabase->table('user')->select('');
        new \NiftyGrid\DataSource\NDataSource($table);
    }

   protected function createComponentEvidenceGrid() {
       //TODO
   }


}
