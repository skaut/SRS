<?php
declare(strict_types=1);

namespace App\Services;

use App\Model\Program\Block;
use App\Model\Program\BlockRepository;
use App\Model\Program\CategoryRepository;
use App\Model\Program\ProgramRepository;
use App\Model\Program\Room;
use App\Model\Settings\CustomInput\CustomInput;
use App\Model\Settings\CustomInput\CustomInputRepository;
use App\Model\Structure\SubeventRepository;
use App\Model\User\User;
use App\Utils\Helpers;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Kdyby\Translation\Translator;
use Nette;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;


/**
 * Služba pro export do formátu XLSX.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ExcelExportService
{
    use Nette\SmartObject;

    /** @var Spreadsheet */
    private $spreadsheet;

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

    /** @var ProgramService */
    private $programService;


    /**
     * ExcelExportService constructor.
     * @param Translator $translator
     * @param CustomInputRepository $customInputRepository
     * @param BlockRepository $blockRepository
     * @param UserService $userService
     * @param SubeventRepository $subeventRepository
     * @param CategoryRepository $categoryRepository
     * @param ProgramRepository $programRepository
     * @param ProgramService $programService
     */
    public function __construct(Translator $translator, CustomInputRepository $customInputRepository,
                                BlockRepository $blockRepository, UserService $userService,
                                SubeventRepository $subeventRepository, CategoryRepository $categoryRepository,
                                ProgramRepository $programRepository, ProgramService $programService)
    {
        $this->spreadsheet = new Spreadsheet();

        $this->translator = $translator;
        $this->customInputRepository = $customInputRepository;
        $this->blockRepository = $blockRepository;
        $this->userService = $userService;
        $this->subeventRepository = $subeventRepository;
        $this->categoryRepository = $categoryRepository;
        $this->programRepository = $programRepository;
        $this->programService = $programService;
    }

    /**
     * Vyexportuje matici uživatelů a rolí.
     * @param $users
     * @param $roles
     * @param $filename
     * @return ExcelResponse
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function exportUsersRoles($users, $roles, $filename)
    {
        $sheet = $this->spreadsheet->getSheet(0);

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

        return new ExcelResponse($this->spreadsheet, $filename);
    }

    /**
     * Vyexportuje harmonogram uživatele.
     * @param $user
     * @param $filename
     * @return ExcelResponse
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function exportUserSchedule($user, $filename)
    {
        return $this->exportUsersSchedules([$user], $filename);
    }

    /**
     * Vyexportuje harmonogramy uživatelů, každý uživatel na zvlástním listu.
     * @param Collection|User[] $users
     * @param $filename
     * @return ExcelResponse
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function exportUsersSchedules($users, $filename)
    {
        $this->spreadsheet->removeSheetByIndex(0);
        $sheetNumber = 0;

        foreach ($users as $user) {
            $sheet = new Worksheet($this->spreadsheet, Helpers::truncate($user->getDisplayName(), 28));
            $this->spreadsheet->addSheet($sheet, $sheetNumber++);

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

        return new ExcelResponse($this->spreadsheet, $filename);
    }

    /**
     * Vyexportuje harmonogram místnosti.
     * @param Room $room
     * @param $filename
     * @return ExcelResponse
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function exportRoomSchedule(Room $room, $filename)
    {
        return $this->exportRoomsSchedules([$room], $filename);
    }

    /**
     * Vyexportuje harmonogramy místností.
     * @param Collection|Room[] $rooms
     * @param $filename
     * @return ExcelResponse
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function exportRoomsSchedules($rooms, $filename)
    {
        $this->spreadsheet->removeSheetByIndex(0);
        $sheetNumber = 0;

        foreach ($rooms as $room) {
            $sheet = new Worksheet($this->spreadsheet, Helpers::truncate($room->getName(), 28));
            $this->spreadsheet->addSheet($sheet, $sheetNumber++);

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

        return new ExcelResponse($this->spreadsheet, $filename);
    }

    /**
     * @param Collection|User[] $users
     * @param $filename
     * @return ExcelResponse
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function exportUsersList($users, $filename)
    {
        $sheet = $this->spreadsheet->getSheet(0);

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

        $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate('common.export.user.email'));
        $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(TRUE);
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(FALSE);
        $sheet->getColumnDimensionByColumn($column++)->setWidth('30');

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

                case CustomInput::FILE:
                    continue 2;

                default:
                    throw new \InvalidArgumentException();
            }

            $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate($customInput->getName()));
            $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(TRUE);
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(FALSE);
            $sheet->getColumnDimensionByColumn($column++)->setWidth($width);
        }

        $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate('common.export.user.private_note'));
        $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(TRUE);
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(FALSE);
        $sheet->getColumnDimensionByColumn($column++)->setWidth('60');

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
                ->setValueExplicit($this->userService->getMembershipText($user), DataType::TYPE_STRING);

            $sheet->setCellValueByColumnAndRow($column++, $row, $user->getAge());

            $sheet->setCellValueByColumnAndRow($column++, $row, $user->getEmail());

            $sheet->setCellValueByColumnAndRow($column++, $row, $user->getCity());

            $sheet->setCellValueByColumnAndRow($column++, $row, $user->getFee());

            $sheet->setCellValueByColumnAndRow($column++, $row, $user->getFeeRemaining());

            $sheet->getCellByColumnAndRow($column++, $row)
                ->setValueExplicit($user->getVariableSymbolsText(), DataType::TYPE_STRING);

            $sheet->setCellValueByColumnAndRow($column++, $row, $this->userService->getPaymentMethodText($user));

            $sheet->setCellValueByColumnAndRow($column++, $row, $user->getLastPaymentDate() !== NULL ? $user->getLastPaymentDate()->format(Helpers::DATE_FORMAT) : '');

            $sheet->setCellValueByColumnAndRow($column++, $row, $user->getRolesApplicationDate() !== NULL ? $user->getRolesApplicationDate()->format(Helpers::DATE_FORMAT) : '');

            $sheet->setCellValueByColumnAndRow($column++, $row, $user->isAttended()
                ? $this->translator->translate('common.export.common.yes')
                : $this->translator->translate('common.export.common.no')
            );

            $sheet->setCellValueByColumnAndRow($column++, $row, $user->isAllowedRegisterPrograms()
                    ? $this->programService->getUnregisteredUserMandatoryBlocksNamesText($user) : ''
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

                        case CustomInput::FILE:
                            continue 2;

                        default:
                            throw new \InvalidArgumentException();
                    }
                } else
                    $value = '';

                $sheet->setCellValueByColumnAndRow($column++, $row, $value);
            }

            $sheet->setCellValueByColumnAndRow($column, $row, $user->getNote());
            $sheet->getStyleByColumnAndRow($column++, $row)->getAlignment()->setWrapText(TRUE);
        }
        return new ExcelResponse($this->spreadsheet, $filename);
    }

    /**
     * @param Collection|User[] $users
     * @param $filename
     * @return ExcelResponse
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function exportUsersSubeventsAndCategories($users, $filename)
    {
        $sheet = $this->spreadsheet->getSheet(0);

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
                ->setValueExplicit($user->getVariableSymbolsText(), DataType::TYPE_STRING);

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
        return new ExcelResponse($this->spreadsheet, $filename);
    }

    /**
     * @param Collection|Block[] $blocks
     * @param $filename
     * @return ExcelResponse
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function exportBlocksAttendees($blocks, $filename)
    {
        $this->spreadsheet->removeSheetByIndex(0);
        $sheetNumber = 0;

        foreach ($blocks as $block) {
            $sheet = new Worksheet($this->spreadsheet, Helpers::truncate($block->getName(), 28));
            $this->spreadsheet->addSheet($sheet, $sheetNumber++);

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

        return new ExcelResponse($this->spreadsheet, $filename);
    }
}

