<?php

namespace Tests\Unit;

use App\Traits\ProtectsVerificationExcelPages;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use Tests\TestCase;

class VerificationExcelImagePreservationTest extends TestCase
{
    public function test_preparation_expands_stale_print_area_and_preserves_drawings(): void
    {
        $tmpDir = storage_path('app/tmp');

        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0777, true);
        }

        $suffix = uniqid('verification_excel_', true);
        $imagePath = $tmpDir . DIRECTORY_SEPARATOR . $suffix . '.png';
        $sourcePath = $tmpDir . DIRECTORY_SEPARATOR . $suffix . '.xlsx';
        $preparedPath = null;

        try {
            file_put_contents(
                $imagePath,
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=')
            );

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setCellValue('A1', 'Hasil Verifikasi');
            $sheet->setCellValue('A30', 'Isi setelah gambar');
            $sheet->getPageSetup()->setPrintArea('A1:I15');

            $drawing = new Drawing();
            $drawing->setPath($imagePath);
            $drawing->setCoordinates('B18');
            $drawing->setResizeProportional(false);
            $drawing->setWidth(160);
            $drawing->setHeight(160);
            $drawing->setWorksheet($sheet);

            IOFactory::createWriter($spreadsheet, 'Xlsx')->save($sourcePath);
            $spreadsheet->disconnectWorksheets();

            $preparer = new class {
                use ProtectsVerificationExcelPages {
                    prepareVerificationExcelForPdf as public;
                }
            };

            $preparedPath = $preparer->prepareVerificationExcelForPdf($sourcePath);

            $this->assertNotNull($preparedPath);
            $this->assertFileExists($preparedPath);

            $prepared = IOFactory::load($preparedPath);
            $preparedSheet = $prepared->getActiveSheet();

            $this->assertSame('A1:I30', $preparedSheet->getPageSetup()->getPrintArea());
            $this->assertCount(1, $preparedSheet->getDrawingCollection());
            $this->assertSame('B18', $preparedSheet->getDrawingCollection()[0]->getCoordinates());

            $prepared->disconnectWorksheets();
        } finally {
            foreach ([$imagePath, $sourcePath, $preparedPath] as $path) {
                if (is_string($path) && file_exists($path)) {
                    unlink($path);
                }
            }
        }
    }
}
