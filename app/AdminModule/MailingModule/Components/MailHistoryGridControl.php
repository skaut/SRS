<?php

declare(strict_types=1);

namespace App\AdminModule\MailingModule\Components;

use App\Model\Acl\Role;
use App\Model\Mailing\MailRepository;
use App\Services\AclService;
use App\Services\SubeventService;
use App\Utils\Helpers;
use Doctrine\ORM\QueryBuilder;
use Nette\Application\UI\Control;
use Nette\Localization\ITranslator;
use Nette\Utils\ArrayHash;
use Ublaboo\DataGrid\DataGrid;

/**
 * Komponenta pro výpis historie e-mailů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class MailHistoryGridControl extends Control
{
    private ITranslator $translator;

    private MailRepository $mailRepository;

    private AclService $aclService;

    private SubeventService $subeventService;

    public function __construct(
        ITranslator $translator,
        MailRepository $mailRepository,
        AclService $aclService,
        SubeventService $subeventService
    ) {
        $this->translator      = $translator;
        $this->mailRepository  = $mailRepository;
        $this->aclService      = $aclService;
        $this->subeventService = $subeventService;
    }

    /**
     * Vykreslí komponentu.
     */
    public function render() : void
    {
        $this->template->setFile(__DIR__ . '/templates/mail_history_grid.latte');
        $this->template->render();
    }

    /**
     * Vytvoří komponentu.
     */
    public function createComponentMailHistoryGrid(string $name) : void
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->mailRepository->createQueryBuilder('m'));
        $grid->setDefaultSort(['datetime' => 'DESC']);
        $grid->setItemsPerPageList([25, 50, 100, 250, 500]);
        $grid->setStrictSessionFilterValues(false);

        $grid->addColumnText('recipientRoles', 'admin.mailing.history.recipient_roles', 'recipientRolesText')
            ->setFilterMultiSelect($this->aclService->getRolesWithoutRolesOptions([Role::GUEST, Role::UNAPPROVED, Role::NONREGISTERED]))
            ->setCondition(static function (QueryBuilder $qb, ArrayHash $values) : void {
                $qb->join('m.recipientRoles', 'r')
                    ->andWhere('r.id IN (:rids)')
                    ->setParameter('rids', (array) $values);
            });

        $grid->addColumnText('recipientSubevents', 'admin.mailing.history.recipient_subevents', 'recipientSubeventsText')
            ->setFilterMultiSelect($this->subeventService->getSubeventsOptions())
            ->setCondition(static function (QueryBuilder $qb, ArrayHash $values) : void {
                $qb->join('m.recipientSubevents', 's')
                    ->andWhere('s.id IN (:sids)')
                    ->setParameter('sids', (array) $values);
            });

        $grid->addColumnText('recipientUsers', 'admin.mailing.history.recipient_users', 'recipientUsersText')
            ->setFilterText()
            ->setCondition(static function (QueryBuilder $qb, string $value) : void {
                $qb->join('m.recipientUsers', 'u')
                    ->andWhere('u.displayName LIKE :displayName')
                    ->setParameter('displayName', '%' . $value . '%');
            });

        $grid->addColumnText('subject', 'admin.mailing.history.subject')
            ->setFilterText();

        $grid->addColumnDateTime('datetime', 'admin.mailing.history.datetime')
            ->setFormat(Helpers::DATETIME_FORMAT);

        $automaticText = $grid->addColumnText('automatic', 'admin.mailing.history.automatic');
        $automaticText->setReplacement([
            '0' => $this->translator->translate('admin.common.no'),
            '1' => $this->translator->translate('admin.common.yes'),
        ]);
        $automaticText->setFilterSelect([
            '' => 'admin.common.all',
            '0' => 'admin.common.no',
            '1' => 'admin.common.yes',
        ])->setTranslateOptions();
    }
}
