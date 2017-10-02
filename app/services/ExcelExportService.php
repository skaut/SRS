<?php

namespace App\Services;

use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Model\Program\BlockRepository;
use App\Model\Program\Room;
use App\Model\Settings\CustomInput\CustomInput;
use App\Model\Settings\CustomInput\CustomInputRepository;
use App\Model\User\User;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Kdyby\Translation\Translator;
use Nette;
use PHPExcel_Cell_DataType;


/**
 * Služba pro export do formátu XLSX.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ExcelExportService extends Nette\Object
{
    /** @var \PHPExcel */
    private $phpExcel;

    /** @var Translator */
    private $translator;

    /** @var CustomInputRepository */
    private $customInputRepository;

    /** @var BlockRepository */
    private $blockRepository;


    /**
     * ExcelExportService constructor.
     * @param Translator $translator
     * @param CustomInputRepository $customInputRepository
     * @param BlockRepository $blockRepository
     */
    public function __construct(Translator $translator, CustomInputRepository $customInputRepository,
                                BlockRepository $blockRepository)
    {
        $this->phpExcel = new \PHPExcel();

        $this->translator = $translator;
        $this->customInputRepository = $customInputRepository;
        $this->blockRepository = $blockRepository;
    }

    /**
     * Vyexportuje matici uživatelů a rolí.
     * @param $users
     * @param $roles
     * @param $filename
     * @return ExcelResponse
     */
    public function exportUsersRoles($users, $roles, $filename)
    {
        $sheet = $this->phpExcel->getSheet(0);

        $row = 1;
        $column = 0;

        $sheet->getColumnDimensionByColumn($column)->setAutoSize(FALSE);
        $sheet->getColumnDimensionByColumn($column++)->setWidth('25');

        foreach ($roles as $role) {
            $sheet->setCellValueByColumnAndRow($column, $row, $role->getName());
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(FALSE);
            $sheet->getColumnDimensionByColumn($column)->setWidth('15');
            $column++;
        }

        foreach ($users as $user) {
            $row++;
            $column = 0;

            $sheet->setCellValueByColumnAndRow($column, $row, $user->getDisplayName());

            foreach ($roles as $role) {
                $column++;
                if ($user->isInRole($role))
                    $sheet->setCellValueByColumnAndRow($column, $row, "X");
            }
        }

        return new ExcelResponse($this->phpExcel, $filename);
    }

    /**
     * Vyexportuje harmonogramy uživatelů, každý uživatel na zvlástním listu.
     * @param $users
     * @param $filename
     * @return ExcelResponse
     */
    public function exportUsersSchedules($users, $filename)
    {
        $this->phpExcel->removeSheetByIndex(0);
        $sheetNumber = 0;

        foreach ($users as $user) {
            $sheet = new \PHPExcel_Worksheet($this->phpExcel, $user->getDisplayName());
            $this->phpExcel->addSheet($sheet, $sheetNumber++);

            $row = 1;
            $column = 0;

            $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate('common.export.schedule.from'));
            $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(TRUE);
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(FALSE);
            $sheet->getColumnDimensionByColumn($column++)->setWidth('15');

            $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate('common.export.schedule.to'));
            $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(TRUE);
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(FALSE);
            $sheet->getColumnDimensionByColumn($column++)->setWidth('15');

            $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate('common.export.schedule.program_name'));
            $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(TRUE);
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(FALSE);
            $sheet->getColumnDimensionByColumn($column++)->setWidth('30');

            $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate('common.export.schedule.room'));
            $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(TRUE);
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(FALSE);
            $sheet->getColumnDimensionByColumn($column++)->setWidth('25');

            $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate('common.export.schedule.lector'));
            $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(TRUE);
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(FALSE);
            $sheet->getColumnDimensionByColumn($column++)->setWidth('25');

            foreach ($user->getPrograms() as $program) {
                $row++;
                $column = 0;

                $sheet->setCellValueByColumnAndRow($column++, $row, $program->getStart()->format("j. n. H:i"));
                $sheet->setCellValueByColumnAndRow($column++, $row, $program->getEnd()->format("j. n. H:i"));
                $sheet->setCellValueByColumnAndRow($column++, $row, $program->getBlock()->getName());
                $sheet->setCellValueByColumnAndRow($column++, $row, $program->getRoom() ? $program->getRoom()->getName() : NULL);
                $sheet->setCellValueByColumnAndRow($column++, $row, $program->getBlock()->getLector() ? $program->getBlock()->getLector()->getDisplayName() : NULL);
            }
        }

        return new ExcelResponse($this->phpExcel, $filename);
    }

    /**
     * Vyexportuje harmonogram uživatele.
     * @param $user
     * @param $filename
     * @return ExcelResponse
     */
    public function exportUsersSchedule($user, $filename)
    {
        return $this->exportUsersSchedules([$user], $filename);
    }

    /**
     * Vyexportuje harmonogram místnosti.
     * @param Room $room
     * @param $filename
     * @return ExcelResponse
     */
    public function exportRoomsSchedule(Room $room, $filename)
    {
        $sheet = $this->phpExcel->getSheet(0);

        $row = 1;
        $column = 0;

        $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate('common.export.schedule.from'));
        $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(TRUE);
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(FALSE);
        $sheet->getColumnDimensionByColumn($column++)->setWidth('15');

        $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate('common.export.schedule.to'));
        $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(TRUE);
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(FALSE);
        $sheet->getColumnDimensionByColumn($column++)->setWidth('15');

        $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate('common.export.schedule.program_name'));
        $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(TRUE);
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(FALSE);
        $sheet->getColumnDimensionByColumn($column++)->setWidth('30');

        $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate('common.export.schedule.occupancy'));
        $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(TRUE);
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(FALSE);
        $sheet->getColumnDimensionByColumn($column++)->setWidth('15');

        foreach ($room->getPrograms() as $program) {
            $row++;
            $column = 0;

            $sheet->setCellValueByColumnAndRow($column++, $row, $program->getStart()->format("j. n. H:i"));
            $sheet->setCellValueByColumnAndRow($column++, $row, $program->getEnd()->format("j. n. H:i"));
            $sheet->setCellValueByColumnAndRow($column++, $row, $program->getBlock()->getName());
            $sheet->setCellValueByColumnAndRow($column++, $row, $room->getCapacity() !== NULL
                ? $program->getAttendeesCount() . '/' . $room->getCapacity()
                : $program->getAttendeesCount()
            );
        }

        return new ExcelResponse($this->phpExcel, $filename);
    }

    /**
     * @param Collection|User[] $users
     * @param $filename
     * @return ExcelResponse
     */
    public function exportUsersList($users, $filename)
    {
        $sheet = $this->phpExcel->getSheet(0);

        $row = 1;
        $column = 0;

        $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate('common.export.user.display_name'));
        $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(TRUE);
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(FALSE);
        $sheet->getColumnDimensionByColumn($column++)->setWidth('30');

        $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate('common.export.user.username'));
        $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(TRUE);
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(FALSE);
        $sheet->getColumnDimensionByColumn($column++)->setWidth('20');

        $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate('common.export.user.roles'));
        $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(TRUE);
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(FALSE);
        $sheet->getColumnDimensionByColumn($column++)->setWidth('30');

        $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate('common.export.user.subevents'));
        $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(TRUE);
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(FALSE);
        $sheet->getColumnDimensionByColumn($column++)->setWidth('30');

        $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate('common.export.user.approved'));
        $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(TRUE);
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(FALSE);
        $sheet->getColumnDimensionByColumn($column++)->setWidth('10');

        $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate('common.export.user.membership'));
        $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(TRUE);
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(FALSE);
        $sheet->getColumnDimensionByColumn($column++)->setWidth('20');

        $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate('common.export.user.age'));
        $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(TRUE);
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(FALSE);
        $sheet->getColumnDimensionByColumn($column++)->setWidth('10');

        $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate('common.export.user.city'));
        $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(TRUE);
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(FALSE);
        $sheet->getColumnDimensionByColumn($column++)->setWidth('20');

        $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate('common.export.user.fee'));
        $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(TRUE);
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(FALSE);
        $sheet->getColumnDimensionByColumn($column++)->setWidth('15');

        $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate('common.export.user.fee_remaining'));
        $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(TRUE);
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(FALSE);
        $sheet->getColumnDimensionByColumn($column++)->setWidth('15');

        $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate('common.export.user.variable_symbol'));
        $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(TRUE);
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(FALSE);
        $sheet->getColumnDimensionByColumn($column++)->setWidth('25');

        $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate('common.export.user.payment_method'));
        $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(TRUE);
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(FALSE);
        $sheet->getColumnDimensionByColumn($column++)->setWidth('15');

        $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate('common.export.user.payment_date'));
        $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(TRUE);
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(FALSE);
        $sheet->getColumnDimensionByColumn($column++)->setWidth('20');

        $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate('common.export.user.first_application_date'));
        $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(TRUE);
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(FALSE);
        $sheet->getColumnDimensionByColumn($column++)->setWidth('20');

        $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate('common.export.user.attended'));
        $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(TRUE);
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(FALSE);
        $sheet->getColumnDimensionByColumn($column++)->setWidth('10');

        $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate('common.export.user.not_registared_mandatory_blocks'));
        $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(TRUE);
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(FALSE);
        $sheet->getColumnDimensionByColumn($column++)->setWidth('30');

        foreach ($this->customInputRepository->findAllOrderedByPosition() as $customInput) {
            $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate($customInput->getName()));
            $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(true);
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);

            switch ($customInput->getType()) {
                case CustomInput::TEXT:
                    $width = '30';
                    break;

                case CustomInput::CHECKBOX:
                    $width = '15';
                    break;

                case CustomInput::SELECT:
                    $width = '30';
                    break;

                default:
                    $width = '30';
            }

            $sheet->getColumnDimensionByColumn($column++)->setWidth($width);
        }

        foreach ($users as $user) {
            $row++;
            $column = 0;

            $sheet->setCellValueByColumnAndRow($column++, $row, $user->getDisplayName());

            $sheet->setCellValueByColumnAndRow($column++, $row, $user->getUsername());

            $roles = [];
            foreach ($user->getRoles() as $role)
                $roles[] = $role->getName();
            $sheet->setCellValueByColumnAndRow($column++, $row, implode(", ", $roles));

            $subevents = [];
            foreach ($user->getSubevents() as $subevent)
                $subevents[] = $subevent->getName();
            $sheet->setCellValueByColumnAndRow($column++, $row, implode(", ", $subevents));

            $sheet->setCellValueByColumnAndRow($column++, $row, $user->isApproved()
                ? $this->translator->translate('common.export.common.yes')
                : $this->translator->translate('common.export.common.no')
            );

            $sheet->getCellByColumnAndRow($column++, $row)->setValueExplicit($user->getUnit() !== NULL
                ? $user->getUnit()
                : (
                    $user->isMember()
                        ? $this->translator->translate('common.export.user.membership_no')
                        : (
                            $user->isExternal()
                                ? $this->translator->translate('common.export.user.membership_external')
                                : $this->translator->translate('common.export.user.membership_not_connected')
                        )
                ),
                PHPExcel_Cell_DataType::TYPE_STRING
            );

            $sheet->setCellValueByColumnAndRow($column++, $row, $user->getAge());

            $sheet->setCellValueByColumnAndRow($column++, $row, $user->getCity());

            $sheet->setCellValueByColumnAndRow($column++, $row, $user->getFee());

            $sheet->setCellValueByColumnAndRow($column++, $row, $user->getFeeRemaining());

            $variableSymbols = [];
            foreach ($user->getApplications() as $application)
                $variableSymbols[] = $application->getVariableSymbol();
            $sheet->setCellValueByColumnAndRow($column++, $row, implode(", ", $variableSymbols));

            $paymentMethod = NULL;
            $paymentMethodName = NULL;
            foreach ($user->getApplications() as $application) {
                $currentPaymentMethod = $application->getPaymentMethod();
                if ($currentPaymentMethod) {
                    if ($paymentMethod === NULL) {
                        $paymentMethod = $currentPaymentMethod;
                        continue;
                    }
                    if ($paymentMethod != $currentPaymentMethod) {
                        $paymentMethodName = $this->translator->translate('common.payment.mixed');
                        break;
                    }
                }
            }
            if ($paymentMethod && !$paymentMethodName)
                $paymentMethodName = $this->translator->translate('common.payment.' . $paymentMethod);
            $sheet->setCellValueByColumnAndRow($column++, $row, $paymentMethodName);

            $sheet->setCellValueByColumnAndRow($column++, $row, $user->getLastPaymentDate() !== NULL ? $user->getLastPaymentDate()->format("j. n. Y") : '');

            $sheet->setCellValueByColumnAndRow($column++, $row, $user->getFirstApplicationDate() !== NULL ? $user->getFirstApplicationDate()->format("j. n. Y") : '');

            $sheet->setCellValueByColumnAndRow($column++, $row, $user->isAttended()
                ? $this->translator->translate('common.export.common.yes')
                : $this->translator->translate('common.export.common.no')
            );

            $sheet->setCellValueByColumnAndRow($column++, $row,
                ($user->isAllowed(Resource::PROGRAM, Permission::CHOOSE_PROGRAMS) && $user->isApproved())
                    ? implode(', ', $this->blockRepository->findUserMandatoryNotRegisteredNames($user))
                    : ''
            );


            foreach ($this->customInputRepository->findAllOrderedByPosition() as $customInput) {
                $customInputValue = $user->getCustomInputValue($customInput);

                if ($customInputValue) {
                    switch ($customInputValue->getInput()->getType()) {
                        case CustomInput::TEXT:
                            $value = $customInputValue->getValue();
                            break;

                        case CustomInput::CHECKBOX:
                            $value = $customInputValue->getValue()
                                ? $this->translator->translate('common.export.common.yes')
                                : $this->translator->translate('common.export.common.no');
                            break;

                        case CustomInput::SELECT:
                            $value = $customInputValue->getValueOption();
                            break;
                    }
                }
                else
                    $value = '';

                $sheet->setCellValueByColumnAndRow($column++, $row, $value);
            }
        }
        return new ExcelResponse($this->phpExcel, $filename);
    }
}
