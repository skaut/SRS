<?php

declare(strict_types=1);

namespace App\Services;

use Nette;
use Nette\Application\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * ExcelResponse.
 */
class ExcelResponse implements Response
{
    use Nette\SmartObject;

    public function __construct(private readonly Spreadsheet $spreadsheet, private readonly string $filename)
    {
    }

    /** @throws Exception */
    public function send(Nette\Http\IRequest $httpRequest, Nette\Http\IResponse $httpResponse): void
    {
        $httpResponse->setContentType('application/force-download');
        $httpResponse->setHeader('Content-Disposition', 'attachment;filename=' . $this->filename);
        $httpResponse->setHeader('Content-Transfer-Encoding', 'binary');

        $writer = new Xlsx($this->spreadsheet);
        $writer->save('php://output');
    }
}
