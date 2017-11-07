<?php

namespace App\Services;

use App\Model\ACL\Permission;
use App\Model\ACL\Resource;
use App\Model\Program\Block;
use App\Model\Program\BlockRepository;
use App\Model\Program\CategoryRepository;
use App\Model\Program\ProgramRepository;
use App\Model\Program\Room;
use App\Model\Settings\CustomInput\CustomInput;
use App\Model\Settings\CustomInput\CustomInputRepository;
use App\Model\Structure\SubeventRepository;
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

    /** @var UserService */
    private $userService;

    /** @var SubeventRepository */
    private $subeventRepository;

    /** @var CategoryRepository */
    private $categoryRepository;

    /** @var ProgramRepository */
    private $programRepository;


    /**
     * ExcelExportService constructor.
     * @param Translator $translator
     * @param CustomInputRepository $customInputRepository
     * @param BlockRepository $blockRepository
     * @param UserService $userService
     * @param SubeventRepository $subeventRepository
     * @param CategoryRepository $categoryRepository
     * @param ProgramRepository $programRepository
     */
    public function __construct(Translator $translator, CustomInputRepository $customInputRepository,
                                BlockRepository $blockRepository, UserService $userService,
                                SubeventRepository $subeventRepository, CategoryRepository $categoryRepository,
                                ProgramRepository $programRepository)
    {
        $this->phpExcel = new \PHPExcel();

        $this->translator = $translator;
        $this->customInputRepository = $customInputRepository;
        $this->blockRepository = $blockRepository;
        $this->userService = $userService;
        $this->subeventRepository = $subeventRepository;
        $this->categoryRepository = $categoryRepository;
        $this->programRepository = $programRepository;
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
            $sheet = new \PHPExcel_Worksheet($this->phpExcel, $this->truncate($user->getDisplayName(), 28));
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
                $sheet->setCellValueByColumnAndRow($column++, $row, $program->getBlock()->getLector() ? $program->getBlock()->getLector()->getLectorName() : NULL);
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
    public function exportUserSchedule($user, $filename)
    {
        return $this->exportUsersSchedules([$user], $filename);
    }

    /**
     * Vyexportuje harmonogramy místností.
     * @param $rooms
     * @param $filename
     * @return ExcelResponse
     */
    public function exportRoomsSchedules($rooms, $filename) {
        $this->phpExcel->removeSheetByIndex(0);
        $sheetNumber = 0;

        foreach ($rooms as $room) {
            $sheet = new \PHPExcel_Worksheet($this->phpExcel, $this->truncate($room->getName(), 28));
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
        }

        return new ExcelResponse($this->phpExcel, $filename);
    }

    /**
     * Vyexportuje harmonogram místnosti.
     * @param Room $room
     * @param $filename
     * @return ExcelResponse
     */
    public function exportRoomSchedule(Room $room, $filename)
    {
        return $this->exportRoomsSchedules([$room], $filename);
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
            $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(TRUE);
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(FALSE);

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

            $sheet->setCellValueByColumnAndRow($column++, $row, $user->getRolesText());

            $sheet->setCellValueByColumnAndRow($column++, $row, $user->getSubeventsText());

            $sheet->setCellValueByColumnAndRow($column++, $row, $user->isApproved()
                ? $this->translator->translate('common.export.common.yes')
                : $this->translator->translate('common.export.common.no')
            );

            $sheet->getCellByColumnAndRow($column++, $row)
                ->setValueExplicit($this->userService->getMembershipText($user), PHPExcel_Cell_DataType::TYPE_STRING);

            $sheet->setCellValueByColumnAndRow($column++, $row, $user->getAge());

            $sheet->setCellValueByColumnAndRow($column++, $row, $user->getCity());

            $sheet->setCellValueByColumnAndRow($column++, $row, $user->getFee());

            $sheet->setCellValueByColumnAndRow($column++, $row, $user->getFeeRemaining());

            $sheet->getCellByColumnAndRow($column++, $row)
                ->setValueExplicit($user->getVariableSymbolsText(), PHPExcel_Cell_DataType::TYPE_STRING);

            $sheet->setCellValueByColumnAndRow($column++, $row, $this->userService->getPaymentMethodText($user));

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

    /**
     * @param Collection|User[] $users
     * @param $filename
     * @return ExcelResponse
     */
    public function exportUsersSubeventsAndCategories($users, $filename) {
        $sheet = $this->phpExcel->getSheet(0);

        $row = 1;
        $column = 0;

        $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate('common.export.user.variable_symbol'));
        $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(TRUE);
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(FALSE);
        $sheet->getColumnDimensionByColumn($column++)->setWidth('20');

        $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate('common.export.user.first_name'));
        $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(TRUE);
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(FALSE);
        $sheet->getColumnDimensionByColumn($column++)->setWidth('20');

        $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate('common.export.user.last_name'));
        $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(TRUE);
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(FALSE);
        $sheet->getColumnDimensionByColumn($column++)->setWidth('20');

        $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate('common.export.user.nickname'));
        $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(TRUE);
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(FALSE);
        $sheet->getColumnDimensionByColumn($column++)->setWidth('20');

        foreach ($this->subeventRepository->findAllExplicitOrderedByName() as $subevent) {
            $sheet->setCellValueByColumnAndRow($column, $row, $subevent->getName());
            $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(TRUE);
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(FALSE);
            $sheet->getColumnDimensionByColumn($column++)->setWidth('10');
        }

        foreach ($this->categoryRepository->findAll() as $category) {
            $sheet->setCellValueByColumnAndRow($column, $row, $category->getName() . ' - ' . $this->translator->translate('common.export.schedule.program_name'));
            $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(TRUE);
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(FALSE);
            $sheet->getColumnDimensionByColumn($column++)->setWidth('20');

            $sheet->setCellValueByColumnAndRow($column, $row, $category->getName() . ' - ' . $this->translator->translate('common.export.schedule.room'));
            $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(TRUE);
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(FALSE);
            $sheet->getColumnDimensionByColumn($column++)->setWidth('10');
        }

        foreach ($users as $user) {
            $row++;
            $column = 0;

            $sheet->getCellByColumnAndRow($column++, $row)
                ->setValueExplicit($user->getFirstApplication() ? $user->getFirstApplication()->getVariableSymbol() : '',
                    PHPExcel_Cell_DataType::TYPE_STRING);

            $sheet->setCellValueByColumnAndRow($column++, $row, $user->getFirstName());

            $sheet->setCellValueByColumnAndRow($column++, $row, $user->getLastName());

            $sheet->setCellValueByColumnAndRow($column++, $row, $user->getNickname());

            foreach ($this->subeventRepository->findAllExplicitOrderedByName() as $subevent) {
                $sheet->setCellValueByColumnAndRow($column++, $row, $user->hasSubevent($subevent)
                    ? $this->translator->translate('common.export.common.yes')
                    : $this->translator->translate('common.export.common.no'));
            }

            foreach ($this->categoryRepository->findAll() as $category) {
                $blocks = [];
                $rooms = [];
                foreach ($this->programRepository->findUserRegisteredAndInCategory($user, $category) as $program) {
                    $blocks[] = $program->getBlock()->getName();
                    $rooms[] = $program->getRoom() ? $program->getRoom()->getName() : "";
                }

                $sheet->setCellValueByColumnAndRow($column++, $row, implode(', ', $blocks));
                $sheet->setCellValueByColumnAndRow($column++, $row, implode(', ', $rooms));
            }
        }
        return new ExcelResponse($this->phpExcel, $filename);
    }

    /**
     * @param Collection|Block[] $blocks
     * @param $filename
     * @return ExcelResponse
     */
    public function exportBlocksAttendees($blocks, $filename)
    {
        $this->phpExcel->removeSheetByIndex(0);
        $sheetNumber = 0;

        foreach ($blocks as $block) {
            $sheet = new \PHPExcel_Worksheet($this->phpExcel, $this->truncate($block->getName(), 28));
            $this->phpExcel->addSheet($sheet, $sheetNumber++);

            $row = 1;
            $column = 0;

            $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate('common.export.user.display_name'));
            $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(TRUE);
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(FALSE);
            $sheet->getColumnDimensionByColumn($column++)->setWidth('30');

            $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate('common.export.user.email'));
            $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(TRUE);
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(FALSE);
            $sheet->getColumnDimensionByColumn($column++)->setWidth('30');

            $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate('common.export.user.address'));
            $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(TRUE);
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(FALSE);
            $sheet->getColumnDimensionByColumn($column++)->setWidth('40');

            $criteria = Criteria::create()->orderBy(['displayName', 'ASC']);

            foreach ($block->getAttendees()->matching($criteria) as $attendee) {
                $row++;
                $column = 0;

                $sheet->setCellValueByColumnAndRow($column++, $row, $attendee->getDisplayName());
                $sheet->setCellValueByColumnAndRow($column++, $row, $attendee->getEmail());
                $sheet->setCellValueByColumnAndRow($column++, $row, $attendee->getAddress());
            }
        }

        return new ExcelResponse($this->phpExcel, $filename);
    }

    /**
     * Zkrátí $text na $length znaků a doplní '...'.
     * @param $text
     * @param $length
     * @return bool|string
     */
    private function truncate($text, $length)
    {
        if (strlen($text) > $length) {
            $text = $text . " ";
            $text = mb_substr($text, 0, $length, 'UTF-8');
            $text = mb_substr($text, 0, strrpos($text, ' '), 'UTF-8');
            $text = $text . "...";
        }
        return $text;
    }
}

