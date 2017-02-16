<?php

namespace App\Services;


use Nette;

class ExcelExportService extends Nette\Object
{
    /** @var \PHPExcel */
    private $phpExcel;


    /**
     * ExcelExportService constructor.
     * @param \PHPExcel $phpExcel
     */
    public function __construct()
    {
        $this->phpExcel = new \PHPExcel();
    }

    public function exportUsersRoles($users, $roles, $filename) {
        $sheet = $this->phpExcel->getSheet(0);

        $row = 1;
        $column = 0;

        $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
        $sheet->getColumnDimensionByColumn($column++)->setWidth('25');

        foreach($roles as $role) {
            $sheet->setCellValueByColumnAndRow($column, $row, $role->getName());
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
            $sheet->getColumnDimensionByColumn($column)->setWidth('15');
            $column++;
        }

        foreach ($users as $user) {
            $row++;
            $column = 0;

            $sheet->setCellValueByColumnAndRow($column, $row, $user->getDisplayName());

            foreach($roles as $role) {
                $column++;
                if ($user->isInRole($role))
                    $sheet->setCellValueByColumnAndRow($column, $row, "X");
            }
        }

        return new ExcelResponse($this->phpExcel, $filename);
    }

    public function exportUsersSchedules($users, $filename) {
        $this->phpExcel->removeSheetByIndex(0);
        $sheetNumber = 0;

        foreach($users as $user) {
            $sheet = new \PHPExcel_Worksheet($this->phpExcel, $user->getDisplayName());
            $this->phpExcel->addSheet($sheet, $sheetNumber++);

            $row = 1;
            $column = 0;

            $sheet->setCellValueByColumnAndRow($column, $row, "Od");
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
            $sheet->getColumnDimensionByColumn($column++)->setWidth('15');

            $sheet->setCellValueByColumnAndRow($column, $row, "Do");
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
            $sheet->getColumnDimensionByColumn($column++)->setWidth('15');

            $sheet->setCellValueByColumnAndRow($column, $row, "Název programu");
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
            $sheet->getColumnDimensionByColumn($column++)->setWidth('30');

            $sheet->setCellValueByColumnAndRow($column, $row, "Místnost");
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
            $sheet->getColumnDimensionByColumn($column++)->setWidth('25');

            $sheet->setCellValueByColumnAndRow($column, $row, "Lektor");
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
            $sheet->getColumnDimensionByColumn($column++)->setWidth('25');

            foreach ($user->getPrograms() as $program) {
                $row++;
                $column = 0;

                $sheet->setCellValueByColumnAndRow($column++, $row, $program->getStart()->format("j. n. H:i"));
                $sheet->setCellValueByColumnAndRow($column++, $row, $program->getEnd()->format("j. n. H:i"));
                $sheet->setCellValueByColumnAndRow($column++, $row, $program->getBlock()->getName());
                $sheet->setCellValueByColumnAndRow($column++, $row, $program->getRoom() ? $program->getRoom()->getName() : null);
                $sheet->setCellValueByColumnAndRow($column++, $row, $program->getBlock()->getLector() ? $program->getBlock()->getLector()->getDisplayName() : null);
            }
        }

        return new ExcelResponse($this->phpExcel, $filename);
    }

    public function exportUsersSchedule($user, $filename) {
        return $this->exportUsersSchedules([$user], $filename);
    }
}