<?php
declare(strict_types=1);

namespace App\AdminModule\MailingModule\Components;

use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\Mailing\MailRepository;
use App\Utils\Helpers;
use Doctrine\ORM\QueryBuilder;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
use Ublaboo\DataGrid\DataGrid;


/**
 * Komponenta pro výpis historie e-mailů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class MailHistoryGridControl extends Control
{
    /** @var Translator */
    private $translator;

    /** @var MailRepository */
    private $mailRepository;

    /** @var RoleRepository */
    private $roleRepository;


    /**
     * MailHistoryGridControl constructor.
     * @param Translator $translator
     * @param MailRepository $mailRepository
     * @param RoleRepository $roleRepository
     */
    public function __construct(Translator $translator, MailRepository $mailRepository, RoleRepository $roleRepository)
    {
        parent::__construct();

        $this->translator = $translator;
        $this->mailRepository = $mailRepository;
        $this->roleRepository = $roleRepository;
    }

    /**
     * Vykreslí komponentu.
     */
    public function render()
    {
        $this->template->render(__DIR__ . '/templates/mail_history_grid.latte');
    }

    /**
     * Vytvoří komponentu.
     * @param $name
     */
    public function createComponentMailHistoryGrid($name)
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->mailRepository->createQueryBuilder('m'));
        $grid->setDefaultSort(['datetime' => 'DESC']);
        $grid->setItemsPerPageList([25, 50, 100, 250, 500]);

        $grid->addColumnText('recipientRoles', 'admin.mailing.history_recipient_roles', 'recipientRolesText')
            ->setFilterMultiSelect($this->roleRepository->getRolesWithoutRolesOptions([Role::GUEST, Role::UNAPPROVED, Role::NONREGISTERED]))
            ->setCondition(function (QueryBuilder $qb, $values) {
                $qb->join('m.recipientRoles', 'r')
                    ->andWhere('r.id IN (:rids)')
                    ->setParameter('rids', $values);
            });

        $grid->addColumnText('recipientUsers', 'admin.mailing.history_recipient_users', 'recipientUsersText')
            ->setFilterText()
            ->setCondition(function (QueryBuilder $qb, $value) {
                $qb->join('m.recipientUsers', 'u')
                    ->andWhere('u.displayName LIKE :displayName')
                    ->setParameter('displayName', '%' . $value . '%');
            });

        $grid->addColumnText('subject', 'admin.mailing.history_subject')
            ->setFilterText();

        $grid->addColumnDateTime('datetime', 'admin.mailing.history_datetime')
            ->setFormat(Helpers::DATETIME_FORMAT);

        $grid->addColumnText('automatic', 'admin.mailing.history_automatic')
            ->setReplacement([
                '0' => $this->translator->translate('admin.common.no'),
                '1' => $this->translator->translate('admin.common.yes')
            ])
            ->setFilterSelect([
                '' => 'admin.common.all',
                '0' => 'admin.common.no',
                '1' => 'admin.common.yes'
            ])
            ->setTranslateOptions();
    }
}
