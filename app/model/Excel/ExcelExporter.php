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
        $column = 0;

        $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
        $sheet->getColumnDimensionByColumn($column++)->setWidth('25');

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

            foreach($roles as $role) {
                $column++;
                if ($user->isInRole($role->name))
                    $sheet->setCellValueByColumnAndRow($column, $row, "X");
            }
        }

        return new \SRS\Model\Excel\ExcelResponse($this->phpExcel, $filename);
    }

    public function exportMiquik($users, $roles) {
        $filename = "miquik-vstupenky.xlsx";

        $sheet = $this->phpExcel->getSheet(0);

        $row = 1;
        $column = 0;

        //header
        $sheet->setCellValueByColumnAndRow($column, $row, "variabilní symbol");
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
        $sheet->getColumnDimensionByColumn($column++)->setWidth('15');

        $sheet->setCellValueByColumnAndRow($column, $row, "jméno");
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
        $sheet->getColumnDimensionByColumn($column++)->setWidth('15');

        $sheet->setCellValueByColumnAndRow($column, $row, "příjmení");
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
        $sheet->getColumnDimensionByColumn($column++)->setWidth('15');

        $sheet->setCellValueByColumnAndRow($column, $row, "polibek");
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
        $sheet->getColumnDimensionByColumn($column++)->setWidth('15');

        $sheet->setCellValueByColumnAndRow($column, $row, "taneční večer");
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
        $sheet->getColumnDimensionByColumn($column++)->setWidth('15');

        $sheet->setCellValueByColumnAndRow($column, $row, "filmová sobota");
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
        $sheet->getColumnDimensionByColumn($column++)->setWidth('15');

        $sheet->setCellValueByColumnAndRow($column, $row, "nedělní snídaně");
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
        $sheet->getColumnDimensionByColumn($column++)->setWidth('15');

        $sheet->setCellValueByColumnAndRow($column, $row, "mhd");
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
        $sheet->getColumnDimensionByColumn($column++)->setWidth('15');

        $sheet->setCellValueByColumnAndRow($column, $row, "1. sekce název přednášky");
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
        $sheet->getColumnDimensionByColumn($column++)->setWidth('25');

        $sheet->setCellValueByColumnAndRow($column, $row, "1. sekce číslo místnosti");
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
        $sheet->getColumnDimensionByColumn($column++)->setWidth('25');

        $sheet->setCellValueByColumnAndRow($column, $row, "2. sekce název přednášky");
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
        $sheet->getColumnDimensionByColumn($column++)->setWidth('25');

        $sheet->setCellValueByColumnAndRow($column, $row, "2. sekce číslo místnosti");
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
        $sheet->getColumnDimensionByColumn($column++)->setWidth('25');

        $sheet->setCellValueByColumnAndRow($column, $row, "3. sekce název přednášky");
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
        $sheet->getColumnDimensionByColumn($column++)->setWidth('25');

        $sheet->setCellValueByColumnAndRow($column, $row, "3. sekce číslo místnosti");
        $sheet->getColumnDimensionByColumn($column)->setAutoSize(false);
        $sheet->getColumnDimensionByColumn($column++)->setWidth('25');

        foreach ($users as $user) {
            $row++;
            $column = 0;

            $sheet->setCellValueByColumnAndRow($column++, $row, $user->variableSymbol);
            $sheet->setCellValueByColumnAndRow($column++, $row, $user->firstName);
            $sheet->setCellValueByColumnAndRow($column++, $row, $user->lastName);

            $sheet->setCellValueByColumnAndRow($column++, $row, $user->isInRole("Polibek Múzy / Komponovaný večer") ? "Y" : "N");
            $sheet->setCellValueByColumnAndRow($column++, $row, $user->isInRole("Taneční večer") ? "Y" : "N");
            $sheet->setCellValueByColumnAndRow($column++, $row, $user->isInRole("Filmová sobota") ? "Y" : "N");
            $sheet->setCellValueByColumnAndRow($column++, $row, $user->isInRole("Nedělní snídaně") ? "Y" : "N");
            $sheet->setCellValueByColumnAndRow($column++, $row, $user->isInRole("MHD studenti") ? "Y" : "N");

            foreach($user->programs as $program) {
                if ($program->start->format("d.m.Y H:i") == "03.12.2016 10:20") {
                    $sheet->setCellValueByColumnAndRow(8, $row, $program->block->name);
                    if ($program->block->room !== null)
                        $sheet->setCellValueByColumnAndRow(9, $row, $program->block->room->name);
                }
                if ($program->start->format("d.m.Y H:i") == "03.12.2016 11:40") {
                    $sheet->setCellValueByColumnAndRow(10, $row, $program->block->name);
                    if ($program->block->room !== null)
                        $sheet->setCellValueByColumnAndRow(11, $row, $program->block->room->name);
                }
                if ($program->start->format("d.m.Y H:i") == "03.12.2016 13:30") {
                    $sheet->setCellValueByColumnAndRow(12, $row, $program->block->name);
                    if ($program->block->room !== null)
                        $sheet->setCellValueByColumnAndRow(13, $row, $program->block->room->name);
                }
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