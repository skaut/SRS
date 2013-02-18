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

    /**
     * @var \Nella\Doctrine\Repository
     */
    protected $userRepo;

    public function startup() {
        parent::startup();
        $this->userRepo = $this->context->database->getRepository('\SRS\Model\User');
    }

    public function beforeRender() {
        parent::beforeRender();

    }

    public function renderList() {
//        $dbParams = $this->getDBParams();
//        $dsn = "mysql:host={$dbParams['host']};dbname={$dbParams['dbname']}";
//        $netteDatabase = new \Nette\Database\Connection($dsn, $dbParams['user'], $dbParams['password']);
//
//        $query = "
//        user.id, firstName, ex.value, ex.property left join (SELECT ex.user_id , ex.value as food FROM userextension ex WHERE ex.property='food') as ex on ex.user_id = user.id
//        ";
//        $query = "user.id, firstName, role.name";
//        $table = $netteDatabase->table('user')->select($query);
//
//
//        $sql = $table->getSql();
//        \Nette\Diagnostics\Debugger::dump('ahoj');
//        \Nette\Diagnostics\Debugger::dump($sql);
//        echo $sql;
//        \Nette\Diagnostics\Debugger::dump($table->fetch());
    }

    public function renderDetail($id)
    {
        $user = $this->userRepo->find($id);
        if ($user == null) {
            throw new \Nette\Application\BadRequestException("Takový uživatel neexistuje", 404);
        }
        $form = $this->getComponent('evidenceDetailForm');
        $form->bindEntity($user);

        //$user je v template defaultne
        $this->template->dbuser = $user;
    }


    protected function createComponentEvidenceGrid() {
       return new \SRS\Components\EvidenceGrid($this->context->database);
   }

    protected function createComponentEvidenceDetailForm()
    {
        return new \SRS\Form\Evidence\EvidenceDetailForm();
    }


}
