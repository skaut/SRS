<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\Acl\Role;
use App\Model\CustomInput\CustomCheckboxValue;
use App\Model\CustomInput\CustomInput;
use App\Model\CustomInput\Repositories\CustomInputRepository;
use App\Model\Program\Block;
use App\Model\Program\Queries\BlockAttendeesQuery;
use App\Model\Program\Repositories\CategoryRepository;
use App\Model\Program\Repositories\ProgramRepository;
use App\Model\Program\Room;
use App\Model\Structure\Repositories\SubeventRepository;
use App\Model\User\Queries\UserAttendsProgramsQuery;
use App\Model\User\User;
use App\Utils\Helpers;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Exception;
use InvalidArgumentException;
use Nette;
use Nette\Localization\Translator;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

use function implode;
use function preg_replace;

/**
 * Služba pro export do formátu XLSX.
 */
class ExcelExportService
{
    use Nette\SmartObject;

    private Spreadsheet $spreadsheet;

    public function __construct(
        private Translator $translator,
        private CustomInputRepository $customInputRepository,
        private UserService $userService,
        private SubeventRepository $subeventRepository,
        private CategoryRepository $categoryRepository,
        private ProgramRepository $programRepository,
        private QueryBus $queryBus
    ) {
        $this->spreadsheet = new Spreadsheet();
    }

    /**
     * Vyexportuje matici uživatelů a rolí.
     *
     * @param Collection<int, User> $users
     * @param Collection<int, Role> $roles
     *
     * @throws Exception
     */
    public function exportUsersRoles(Collection $users, Collection $roles, string $filename): ExcelResponse
    {
        $sheet = $this->spreadsheet->getSheet(0);

        $row    = 1;
        $column = 1;

        $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
        $sheet->getColumnDimensionByColumn($column++)->setWidth(25);

        foreach ($roles as $role) {
            $sheet->setCellValueByColumnAndRow($column, $row, $role->getName());
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
            $sheet->getColumnDimensionByColumn($column)->setWidth(15);
            $column++;
        }

        foreach ($users as $user) {
            $row++;
            $column = 1;

            $sheet->setCellValueByColumnAndRow($column, $row, $user->getDisplayName());

            foreach ($roles as $role) {
                $column++;
                if ($user->isInRole($role)) {
                    $sheet->setCellValueByColumnAndRow($column, $row, 'X');
                    $sheet->getStyleByColumnAndRow($column, $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
            }
        }

        return new ExcelResponse($this->spreadsheet, $filename);
    }

    /**
     * Vyexportuje harmonogram uživatele.
     *
     * @throws Exception
     */
    public function exportUserSchedule(User $user, string $filename): ExcelResponse
    {
        return $this->exportUsersSchedules(new ArrayCollection([$user]), $filename);
    }

    /**
     * Vyexportuje harmonogramy uživatelů, každý uživatel na zvlástním listu.
     *
     * @param Collection<int, User> $users
     *
     * @throws Exception
     * @throws Exception
     */
    public function exportUsersSchedules(Collection $users, string $filename): ExcelResponse
    {
        $this->spreadsheet->removeSheetByIndex(0);
        $sheetNumber = 0;

        foreach ($users as $user) {
            $sheet = new Worksheet($this->spreadsheet, self::cleanSheetName($user->getDisplayName()));
            $this->spreadsheet->addSheet($sheet, $sheetNumber++);

            $row    = 1;
            $column = 1;

            $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate('common.export.schedule.from'));
            $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(true);
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
            $sheet->getColumnDimensionByColumn($column++)->setWidth(15);

            $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate('common.export.schedule.to'));
            $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(true);
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
            $sheet->getColumnDimensionByColumn($column++)->setWidth(15);

            $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate('common.export.schedule.program_name'));
            $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(true);
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
            $sheet->getColumnDimensionByColumn($column++)->setWidth(30);

            $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate('common.export.schedule.room'));
            $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(true);
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
            $sheet->getColumnDimensionByColumn($column++)->setWidth(25);

            $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate('common.export.schedule.lectors'));
            $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(true);
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
            $sheet->getColumnDimensionByColumn($column++)->setWidth(25);

            $userPrograms = $this->queryBus->handle(new UserAttendsProgramsQuery($user));

            foreach ($userPrograms as $program) {
                $row++;
                $column = 1;

                $sheet->setCellValueByColumnAndRow($column++, $row, $program->getStart()->format('j. n. H:i'));
                $sheet->setCellValueByColumnAndRow($column++, $row, $program->getEnd()->format('j. n. H:i'));
                $sheet->setCellValueByColumnAndRow($column++, $row, $program->getBlock()->getName());
                $sheet->setCellValueByColumnAndRow($column++, $row, $program->getRoom() ? $program->getRoom()->getName() : null);
                $sheet->setCellValueByColumnAndRow($column++, $row, $program->getBlock()->getLectorsText());
            }
        }

        return new ExcelResponse($this->spreadsheet, $filename);
    }

    /**
     * Vyexportuje harmonogram místnosti.
     *
     * @throws Exception
     */
    public function exportRoomSchedule(Room $room, string $filename): ExcelResponse
    {
        return $this->exportRoomsSchedules(new ArrayCollection([$room]), $filename);
    }

    /**
     * Vyexportuje harmonogramy místností.
     *
     * @param Collection<int, Room> $rooms
     *
     * @throws Exception
     */
    public function exportRoomsSchedules(Collection $rooms, string $filename): ExcelResponse
    {
        $this->spreadsheet->removeSheetByIndex(0);
        $sheetNumber = 0;

        foreach ($rooms as $room) {
            $sheet = new Worksheet($this->spreadsheet, self::cleanSheetName($room->getName()));
            $this->spreadsheet->addSheet($sheet, $sheetNumber++);

            $row    = 1;
            $column = 1;

            $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate('common.export.schedule.from'));
            $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(true);
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
            $sheet->getColumnDimensionByColumn($column++)->setWidth(15);

            $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate('common.export.schedule.to'));
            $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(true);
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
            $sheet->getColumnDimensionByColumn($column++)->setWidth(15);

            $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate('common.export.schedule.program_name'));
            $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(true);
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
            $sheet->getColumnDimensionByColumn($column++)->setWidth(30);

            $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate('common.export.schedule.occupancy'));
            $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(true);
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
            $sheet->getColumnDimensionByColumn($column++)->setWidth(15);

            foreach ($room->getPrograms() as $program) {
                $row++;
                $column = 1;

                $sheet->setCellValueByColumnAndRow($column++, $row, $program->getStart()->format('j. n. H:i'));
                $sheet->setCellValueByColumnAndRow($column++, $row, $program->getEnd()->format('j. n. H:i'));
                $sheet->setCellValueByColumnAndRow($column++, $row, $program->getBlock()->getName());
                $sheet->setCellValueByColumnAndRow($column++, $row, $room->getCapacity() !== null
                    ? $program->getAttendeesCount() . '/' . $room->getCapacity()
                    : $program->getAttendeesCount());
            }
        }

        return new ExcelResponse($this->spreadsheet, $filename);
    }

    /**
     * @param Collection<int, User> $users
     *
     * @throws Exception
     */
    public function exportUsersList(Collection $users, string $filename): ExcelResponse
    {
        $sheet = $this->spreadsheet->getSheet(0);

        $row    = 1;
        $column = 1;

        $sheet->setCellValue([$column, $row], $this->translator->translate('common.export.user.display_name'));
        $sheet->getStyle([$column, $row])->getFont()->setBold(true);
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
        $sheet->getColumnDimensionByColumn($column++)->setWidth(30);

        $sheet->setCellValue([$column, $row], $this->translator->translate('common.export.user.username'));
        $sheet->getStyle([$column, $row])->getFont()->setBold(true);
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
        $sheet->getColumnDimensionByColumn($column++)->setWidth(20);

        $sheet->setCellValue([$column, $row], $this->translator->translate('common.export.user.roles'));
        $sheet->getStyle([$column, $row])->getFont()->setBold(true);
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
        $sheet->getColumnDimensionByColumn($column++)->setWidth(30);

        $sheet->setCellValue([$column, $row], $this->translator->translate('Skupinové role'));
        $sheet->getStyle([$column, $row])->getFont()->setBold(true);
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
        $sheet->getColumnDimensionByColumn($column++)->setWidth(30);

        $sheet->setCellValue([$column, $row], $this->translator->translate('common.export.user.subevents'));
        $sheet->getStyle([$column, $row])->getFont()->setBold(true);
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
        $sheet->getColumnDimensionByColumn($column++)->setWidth(30);

        $sheet->setCellValue([$column, $row], $this->translator->translate('common.export.user.approved'));
        $sheet->getStyle([$column, $row])->getFont()->setBold(true);
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
        $sheet->getColumnDimensionByColumn($column++)->setWidth(10);

        $sheet->setCellValue([$column, $row], $this->translator->translate('common.export.user.membership'));
        $sheet->getStyle([$column, $row])->getFont()->setBold(true);
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
        $sheet->getColumnDimensionByColumn($column++)->setWidth(20);

        $sheet->setCellValue([$column, $row], $this->translator->translate('common.export.user.age'));
        $sheet->getStyle([$column, $row])->getFont()->setBold(true);
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
        $sheet->getColumnDimensionByColumn($column++)->setWidth(10);

        $sheet->setCellValue([$column, $row], $this->translator->translate('common.export.user.email'));
        $sheet->getStyle([$column, $row])->getFont()->setBold(true);
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
        $sheet->getColumnDimensionByColumn($column++)->setWidth(30);

        $sheet->setCellValue([$column, $row], $this->translator->translate('common.export.user.city'));
        $sheet->getStyle([$column, $row])->getFont()->setBold(true);
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
        $sheet->getColumnDimensionByColumn($column++)->setWidth(20);

        $sheet->setCellValue([$column, $row], $this->translator->translate('common.export.user.fee'));
        $sheet->getStyle([$column, $row])->getFont()->setBold(true);
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
        $sheet->getColumnDimensionByColumn($column++)->setWidth(15);

        $sheet->setCellValue([$column, $row], $this->translator->translate('common.export.user.fee_remaining'));
        $sheet->getStyle([$column, $row])->getFont()->setBold(true);
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
        $sheet->getColumnDimensionByColumn($column++)->setWidth(15);

        $sheet->setCellValue([$column, $row], $this->translator->translate('common.export.user.variable_symbol'));
        $sheet->getStyle([$column, $row])->getFont()->setBold(true);
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
        $sheet->getColumnDimensionByColumn($column++)->setWidth(25);

        $sheet->setCellValue([$column, $row], $this->translator->translate('common.export.user.payment_method'));
        $sheet->getStyle([$column, $row])->getFont()->setBold(true);
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
        $sheet->getColumnDimensionByColumn($column++)->setWidth(15);

        $sheet->setCellValue([$column, $row], $this->translator->translate('common.export.user.payment_date'));
        $sheet->getStyle([$column, $row])->getFont()->setBold(true);
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
        $sheet->getColumnDimensionByColumn($column++)->setWidth(20);

        $sheet->setCellValue([$column, $row], $this->translator->translate('common.export.user.first_application_date'));
        $sheet->getStyle([$column, $row])->getFont()->setBold(true);
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
        $sheet->getColumnDimensionByColumn($column++)->setWidth(20);

        $sheet->setCellValue([$column, $row], $this->translator->translate('common.export.user.attended'));
        $sheet->getStyle([$column, $row])->getFont()->setBold(true);
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
        $sheet->getColumnDimensionByColumn($column++)->setWidth(10);

        foreach ($this->customInputRepository->findAllOrderedByPosition() as $customInput) {
            switch ($customInput->getType()) {
                case CustomInput::TEXT:
                case CustomInput::SELECT:
                case CustomInput::MULTISELECT:
                case CustomInput::DATETIME:
                    $width = 30;
                    break;

                case CustomInput::DATE:
                    $width = 20;
                    break;

                case CustomInput::CHECKBOX:
                    $width = 15;
                    break;

                case CustomInput::FILE:
                    continue 2;

                default:
                    throw new InvalidArgumentException();
            }

            $sheet->setCellValue([$column, $row], $this->translator->translate($customInput->getName()));
            $sheet->getStyle([$column, $row])->getFont()->setBold(true);
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
            $sheet->getColumnDimensionByColumn($column++)->setWidth($width);
        }

        $sheet->setCellValue([$column, $row], $this->translator->translate('common.export.user.private_note'));
        $sheet->getStyle([$column, $row])->getFont()->setBold(true);
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
        $sheet->getColumnDimensionByColumn($column++)->setWidth(60);

        foreach ($users as $user) {
            $row++;
            $column = 1;

            $sheet->setCellValue([$column++, $row], $user->getDisplayName());

            $sheet->setCellValue([$column++, $row], $user->getUsername());

            $sheet->setCellValue([$column++, $row], $user->getRolesText());

            $sheet->setCellValue([$column++, $row], $user->getGroupRolesText());

            $sheet->setCellValue([$column++, $row], $user->getSubeventsText());

            $sheet->setCellValue([$column++, $row], $user->isApproved()
                ? $this->translator->translate('common.export.common.yes')
                : $this->translator->translate('common.export.common.no'));

            $sheet->getCell([$column++, $row])->setValueExplicit($this->userService->getMembershipText($user));

            $sheet->setCellValue([$column++, $row], $user->getAge());

            $sheet->setCellValue([$column++, $row], $user->getEmail());

            $sheet->setCellValue([$column++, $row], $user->getCity());

            $sheet->setCellValue([$column++, $row], $user->getFee());

            $sheet->setCellValue([$column++, $row], $user->getFeeRemaining());

            $sheet->getCell([$column++, $row])->setValueExplicit($user->getVariableSymbolsText());

            $sheet->setCellValue([$column++, $row], $user->getPaymentMethod() ? $this->translator->translate('common.payment.' . $user->getPaymentMethod()) : '');

            $sheet->setCellValue([$column++, $row], $user->getLastPaymentDate() !== null ? $user->getLastPaymentDate()->format(Helpers::DATE_FORMAT) : '');

            $sheet->setCellValue([$column++, $row], $user->getRolesApplicationDate() !== null ? $user->getRolesApplicationDate()->format(Helpers::DATE_FORMAT) : '');

            $sheet->setCellValue([$column++, $row], $user->isAttended()
                ? $this->translator->translate('common.export.common.yes')
                : $this->translator->translate('common.export.common.no'));

            foreach ($this->customInputRepository->findAllOrderedByPosition() as $customInput) {
                $customInputValue = $user->getCustomInputValue($customInput);

                if ($customInputValue === null) {
                    $column++;
                    continue;
                }

                if ($customInputValue instanceof CustomCheckboxValue) {
                    $value = $customInputValue->getValue()
                        ? $this->translator->translate('common.export.common.yes')
                        : $this->translator->translate('common.export.common.no');
                } else {
                    $value = $customInputValue->getValueText();
                }

                $sheet->setCellValue([$column++, $row], $value);
            }

            $sheet->setCellValue([$column, $row], $user->getNote());
            $sheet->getStyle([$column++, $row])->getAlignment()->setWrapText(true);
        }

        return new ExcelResponse($this->spreadsheet, $filename);
    }

    /**
     * @param Collection<int, User> $users
     *
     * @throws Exception
     */
    public function exportUsersSubeventsAndCategories(Collection $users, string $filename): ExcelResponse
    {
        $sheet = $this->spreadsheet->getSheet(0);

        $row    = 1;
        $column = 1;

        $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate('common.export.user.variable_symbol'));
        $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(true);
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
        $sheet->getColumnDimensionByColumn($column++)->setWidth(20);

        $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate('common.export.user.first_name'));
        $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(true);
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
        $sheet->getColumnDimensionByColumn($column++)->setWidth(20);

        $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate('common.export.user.last_name'));
        $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(true);
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
        $sheet->getColumnDimensionByColumn($column++)->setWidth(20);

        $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate('common.export.user.nickname'));
        $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(true);
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
        $sheet->getColumnDimensionByColumn($column++)->setWidth(20);

        foreach ($this->subeventRepository->findFilteredSubevents(true, false, false, false) as $subevent) {
            $sheet->setCellValueByColumnAndRow($column, $row, $subevent->getName());
            $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(true);
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
            $sheet->getColumnDimensionByColumn($column++)->setWidth(10);
        }

        foreach ($this->categoryRepository->findAll() as $category) {
            $sheet->setCellValueByColumnAndRow($column, $row, $category->getName() . ' - ' . $this->translator->translate('common.export.schedule.program_name'));
            $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(true);
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
            $sheet->getColumnDimensionByColumn($column++)->setWidth(20);

            $sheet->setCellValueByColumnAndRow($column, $row, $category->getName() . ' - ' . $this->translator->translate('common.export.schedule.room'));
            $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(true);
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
            $sheet->getColumnDimensionByColumn($column++)->setWidth(10);
        }

        foreach ($users as $user) {
            $row++;
            $column = 1;

            $sheet->getCellByColumnAndRow($column++, $row)
                ->setValueExplicit($user->getVariableSymbolsText(), DataType::TYPE_STRING);

            $sheet->setCellValueByColumnAndRow($column++, $row, $user->getFirstName());

            $sheet->setCellValueByColumnAndRow($column++, $row, $user->getLastName());

            $sheet->setCellValueByColumnAndRow($column++, $row, $user->getNickname());

            foreach ($this->subeventRepository->findFilteredSubevents(true, false, false, false) as $subevent) {
                $sheet->setCellValueByColumnAndRow($column++, $row, $user->hasSubevent($subevent)
                    ? $this->translator->translate('common.export.common.yes')
                    : $this->translator->translate('common.export.common.no'));
            }

            foreach ($this->categoryRepository->findAll() as $category) {
                $blocks = [];
                $rooms  = [];
                foreach ($this->programRepository->findUserAttendsAndCategory($user, $category) as $program) {
                    $blocks[] = $program->getBlock()->getName();
                    $rooms[]  = $program->getRoom() ? $program->getRoom()->getName() : '';
                }

                $sheet->setCellValueByColumnAndRow($column++, $row, implode(', ', $blocks));
                $sheet->setCellValueByColumnAndRow($column++, $row, implode(', ', $rooms));
            }
        }

        return new ExcelResponse($this->spreadsheet, $filename);
    }

    /**
     * @param Collection<int, Block> $blocks
     *
     * @throws Exception
     */
    public function exportBlocksAttendees(Collection $blocks, string $filename): ExcelResponse
    {
        $this->spreadsheet->removeSheetByIndex(0);
        $sheetNumber = 0;

        foreach ($blocks as $block) {
            $sheet = new Worksheet($this->spreadsheet, self::cleanSheetName($block->getName()));
            $this->spreadsheet->addSheet($sheet, $sheetNumber++);

            $row    = 1;
            $column = 1;

            $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate('common.export.user.display_name'));
            $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(true);
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
            $sheet->getColumnDimensionByColumn($column++)->setWidth(30);

            $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate('common.export.user.email'));
            $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(true);
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
            $sheet->getColumnDimensionByColumn($column++)->setWidth(30);

            $sheet->setCellValueByColumnAndRow($column, $row, $this->translator->translate('common.export.user.address'));
            $sheet->getStyleByColumnAndRow($column, $row)->getFont()->setBold(true);
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
            $sheet->getColumnDimensionByColumn($column++)->setWidth(40);

            $attendees = $this->queryBus->handle(new BlockAttendeesQuery($block));

            foreach ($attendees as $attendee) {
                $row++;
                $column = 1;

                $sheet->setCellValueByColumnAndRow($column++, $row, $attendee->getDisplayName());
                $sheet->setCellValueByColumnAndRow($column++, $row, $attendee->getEmail());
                $sheet->setCellValueByColumnAndRow($column++, $row, $attendee->getAddress());
            }
        }

        return new ExcelResponse($this->spreadsheet, $filename);
    }

    /**
     * Odstraní z názvu listu zakázané znaky a zkrátí jej.
     * Excel podporuje 31 znaků, při duplicitních názvech doplní na poslední pozici číslo.
     */
    private static function cleanSheetName(string $name): string
    {
        $name = preg_replace('#[]]#', ')', $name);
        $name = preg_replace('#[[]#', '(', $name);
        $name = preg_replace('#[\\/:]#', '-', $name);
        $name = preg_replace('#[*?]#', '', $name);

        return Helpers::truncate($name, 29);
    }
}
