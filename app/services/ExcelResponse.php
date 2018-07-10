<?php
declare(strict_types=1);

namespace App\Services;

use Nette;
use Nette\Application\IResponse;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


/**
 * ExcelResponse.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ExcelResponse implements IResponse
{
    use Nette\SmartObject;
    
    /** @var Spreadsheet */
    private $spreadsheet;

    /** @var string */
    private $filename;


    /**
     * ExcelResponse constructor.
     * @param Spreadsheet $spreadsheet
     * @param $filename
     */
    public function __construct(Spreadsheet $spreadsheet, $filename)
    {
        $this->spreadsheet = $spreadsheet;
        $this->filename = $filename;
    }

    /**
     * @param Nette\Http\IRequest $httpRequest
     * @param Nette\Http\IResponse $httpResponse
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function send(Nette\Http\IRequest $httpRequest, Nette\Http\IResponse $httpResponse)
    {
        $httpResponse->setContentType('application/force-download');
        $httpResponse->setHeader('Content-Disposition', 'attachment;filename=' . $this->filename);
        $httpResponse->setHeader('Content-Transfer-Encoding', 'binary');

        $writer = new Xlsx($this->spreadsheet);
        $writer->save('php://output');
    }
}
