<?php

namespace DIA\ExporterBundle\Driver;

use DIA\ExporterBundle\Helper\DriverHelper;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class XlsxDriver extends DriverHelper
{
    public function handle($data): Response
    {
        $exporter = $this->getExporterManager()->getExporter();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        array_unshift($data, $exporter->headers);

        $sheet->fromArray(array_values($data), null, 'A1', true);

        // Auto width
        $cellIterator = $sheet->getRowIterator()->current()->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(true);
        foreach ($cellIterator as $cell) {
            $sheet->getCell($cell->getColumn() . '1')->getStyle()->getFont()->setBold(true);

            $sheet->getColumnDimension($cell->getColumn())->setAutoSize(true);
        }

        // Filter
        $sheet->setAutoFilter($spreadsheet->getActiveSheet()
            ->calculateWorksheetDimension());

        $writer = new Xlsx($spreadsheet);

        $response =  new StreamedResponse(function () use ($writer) {
                $writer->save('php://output');
        });
        $response->headers->set('Content-Type', 'application/vnd.ms-excel');
        $response->headers->set('Content-Disposition', sprintf('attachment;filename="%s"', $this->getFileName()));
        $response->headers->set('Cache-Control', 'max-age=0');
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->prepare($this->getExporterManager()->getRequest());
        $response->sendHeaders();

        return $response;
    }
}