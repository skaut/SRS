<?php

namespace App\Services;

use Nette;
use Nette\Application\IResponse;


/**
 * ExcelResponse.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ExcelResponse extends Nette\Object implements IResponse
{
    /** @var \PHPExcel */
    private $phpExcel;

    /** @var string */
    private $filename;


    /**
     * ExcelResponse constructor.
     * @param \PHPExcel $phpExcel
     * @param $filename
     */
    public function __construct(\PHPExcel $phpExcel, $filename)
    {
        $this->phpExcel = $phpExcel;
        $this->filename = $filename;
    }

    /**
     * @param Nette\Http\IRequest $httpRequest
     * @param Nette\Http\IResponse $httpResponse
     */
    public function send(Nette\Http\IRequest $httpRequest, Nette\Http\IResponse $httpResponse)
    {
        $httpResponse->setContentType('application/force-download');
        $httpResponse->setHeader('Content-Disposition', 'attachment;filename=' . $this->filename);
        $httpResponse->setHeader('Content-Transfer-Encoding', 'binary');

        $writer = new \PHPExcel_Writer_Excel2007($this->phpExcel);
        $writer->save('php://output');
    }
}
