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
        $filename = "UsersList.xlsx";

        $sheet = $this->phpExcel->getSheet(0);

        $row = 1;
        $column = 1;

        foreach($roles as $role) {
            $sheet->setCellValueByColumnAndRow($column, $row, $role->name);
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
}