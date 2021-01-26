<?php

declare(strict_types=1);

namespace App\Services;

use Nette;
use Nette\Application\IResponse;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * ExcelResponse.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ExcelResponse implements IResponse
{
    use Nette\SmartObject;

    private Spreadsheet $spreadsheet;

    private string $filename;

    public function __construct(Spreadsheet $spreadsheet, string $filename)
    {
        $this->spreadsheet = $spreadsheet;
        $this->filename    = $filename;
    }

    /**
     * @throws Exception
     */
    public function send(Nette\Http\IRequest $httpRequest, Nette\Http\IResponse $httpResponse): void
    {
        $httpResponse->setContentType('application/force-download');
        $httpResponse->setHeader('Content-Disposition', 'attachment;filename=' . $this->filename);
        $httpResponse->setHeader('Content-Transfer-Encoding', 'binary');

        $writer = new Xlsx($this->spreadsheet);
        $writer->save('php://output');
    }
}
