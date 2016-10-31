<?php
/**
 * Created by PhpStorm.
 * User: jan
 * Date: 26.10.2016
 * Time: 22:58
 */

namespace SRS\Model\Excel;

use Nette\Object;

class ExcelExporter extends Object
{
    private $phpExcel;

    public function __construct()
    {
        $this->phpExcel = new \PHPExcel();
    }

    public function exportUsersRoles($users, $roles) {
        $filename = "role-uzivatelu.xlsx";

        $sheet = $this->phpExcel->getSheet(0);

        $row = 1;
        $column = 1;

        foreach($roles as $role) {
            $sheet->setCellValueByColumnAndRow($column, $row, $role->name);
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
            $sheet->getColumnDimensionByColumn($column)->setWidth('15');
            $column++;
        }

        foreach ($users as $user) {
            $row++;
            $column = 0;

            $sheet->setCellValueByColumnAndRow($column, $row, $user->displayName);
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
            $sheet->getColumnDimensionByColumn($column)->setWidth('20');

            foreach($roles as $role) {
                $column++;
                if ($user->isInRole($role->name))
                    $sheet->setCellValueByColumnAndRow($column, $row, "X");
            }
        }

        return new \SRS\Model\Excel\ExcelResponse($this->phpExcel, $filename);
    }

    public function exportUsersSchedules($users, $basicBlockDuration) {
        $filename = "harmonogram.xlsx";

        $this->phpExcel->removeSheetByIndex(0);
        $sheetNumber = 0;

        foreach($users as $user) {
            $sheet = new \PHPExcel_Worksheet($this->phpExcel, $user->displayName);
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

            foreach ($user->programs as $program) {
                $row++;
                $column = 0;

                $sheet->setCellValueByColumnAndRow($column++, $row, $program->start->format("d.m. h:i"));
                $sheet->setCellValueByColumnAndRow($column++, $row, $program->countEnd($basicBlockDuration)->format("d.m. h:i"));
                $sheet->setCellValueByColumnAndRow($column++, $row, $program->block->name);
                $sheet->setCellValueByColumnAndRow($column++, $row, $program->block->room !== null ? $program->block->room->name : "");
                $sheet->setCellValueByColumnAndRow($column++, $row, $program->block->lector !== null ? $program->block->lector->displayName : "");
            }
        }

        return new \SRS\Model\Excel\ExcelResponse($this->phpExcel, $filename);
    }

    public function exportUsersSchedule($user, $basicBlockDuration) {
        return $this->exportUsersSchedules(array($user), $basicBlockDuration);
    }
}