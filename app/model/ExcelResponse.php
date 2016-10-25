<?php
/**
 * Created by PhpStorm.
 * User: jan
 * Date: 25.10.2016
 * Time: 23:11
 */

namespace SRS\Model;

use Nette\Application\IResponse;
use Nette\Object;
use Nette;


class ExcelResponse extends Object implements IResponse {

    private $excelObject;
    private $filename;

    public function __construct(\PHPExcel $excelObject, $filename)
    {
        $this->excelObject = $excelObject;
        $this->filename = $filename;
    }

    public function send(Nette\Http\IRequest $httpRequest, Nette\Http\IResponse $httpResponse)
    {
        $httpResponse->setContentType('application/force-download');
        $httpResponse->setHeader('Content-Disposition', 'attachment;filename='.$this->filename);
        $httpResponse->setHeader('Content-Transfer-Encoding', 'binary');

        $writer = new \PHPExcel_Writer_Excel2007($this->excelObject);
        $writer->save('php://output');
    }
}
