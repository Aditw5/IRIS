<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Shared\Drawing as SharedDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

trait ProtectsVerificationExcelPages
{
    private function clearVerificationWorksheetPageBreaks(Worksheet $sheet): void
    {
        try {
            if (!method_exists($sheet, 'getBreaks')) {
                return;
            }

            $breaks = $sheet->getBreaks();

            if (!is_array($breaks)) {
                return;
            }

            foreach ($breaks as $cell => $breakType) {
                $sheet->setBreak($cell, Worksheet::BREAK_NONE);
            }
        } catch (\Throwable $e) {
            Log::warning('Gagal menghapus page break worksheet laporan verifikasi: ' . $e->getMessage());
        }
    }

    private function verificationWorksheetHasRowBreaks(Worksheet $sheet): bool
    {
        try {
            if (!method_exists($sheet, 'getBreaks')) {
                return false;
            }

            foreach ($sheet->getBreaks() as $breakType) {
                if ($breakType === Worksheet::BREAK_ROW) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Gagal membaca row break worksheet laporan verifikasi: ' . $e->getMessage());
        }

        return false;
    }

    private function clearVerificationWorksheetColumnBreaks(Worksheet $sheet): void
    {
        try {
            if (!method_exists($sheet, 'getBreaks')) {
                return;
            }

            foreach ($sheet->getBreaks() as $cell => $breakType) {
                if ($breakType === Worksheet::BREAK_COLUMN) {
                    $sheet->setBreak($cell, Worksheet::BREAK_NONE);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Gagal menghapus column break worksheet laporan verifikasi: ' . $e->getMessage());
        }
    }

    private function prepareVerificationExcelForPdf(string $excelPath, int $rowsPerPage = 72): ?string
    {
        if (empty($excelPath) || !file_exists($excelPath)) {
            return null;
        }

        $tmpDir = storage_path('app/tmp');

        if (!is_dir($tmpDir)) {
            @mkdir($tmpDir, 0777, true);
        }

        try {
            $reader = IOFactory::createReaderForFile($excelPath);
            $reader->setReadDataOnly(false);
            $spreadsheet = $reader->load($excelPath);

            foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
                $bounds = $this->detectVerificationWorksheetBounds($sheet);

                if (!$bounds) {
                    continue;
                }

                $range = $bounds['start_col'] . $bounds['start_row'] . ':' . $bounds['end_col'] . $bounds['end_row'];
                $hasSourceRowBreaks = $this->verificationWorksheetHasRowBreaks($sheet);

                if ($hasSourceRowBreaks) {
                    $this->clearVerificationWorksheetColumnBreaks($sheet);
                } else {
                    $this->clearVerificationWorksheetPageBreaks($sheet);
                }

                $sheet->getPageSetup()->setPrintArea($range);
                $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
                $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);

                if (method_exists($sheet->getPageSetup(), 'setFitToPage')) {
                    $sheet->getPageSetup()->setFitToPage(true);
                }

                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0);

                if (!$hasSourceRowBreaks) {
                    $this->protectVerificationChannelPageBreaks($sheet, $bounds, $rowsPerPage);
                }

                $sheet->getPageMargins()->setTop(0.10);
                $sheet->getPageMargins()->setRight(0.10);
                $sheet->getPageMargins()->setLeft(0.10);
                $sheet->getPageMargins()->setBottom(0.10);
                $sheet->getPageMargins()->setHeader(0.02);
                $sheet->getPageMargins()->setFooter(0.02);

                if (method_exists($sheet->getPageSetup(), 'setHorizontalCentered')) {
                    $sheet->getPageSetup()->setHorizontalCentered(true);
                }

                if (method_exists($sheet->getPageSetup(), 'setVerticalCentered')) {
                    $sheet->getPageSetup()->setVerticalCentered(false);
                }

                $highestColumn = $sheet->getHighestColumn();
                $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

                for ($colIndex = $bounds['end_col_index'] + 1; $colIndex <= $highestColumnIndex; $colIndex++) {
                    $sheet->getColumnDimensionByColumn($colIndex)->setVisible(false);
                }

                $sheet->setSelectedCell($bounds['start_col'] . $bounds['start_row']);
            }

            $tmpFile = $tmpDir . DIRECTORY_SEPARATOR . 'prepared_laporan_verifikasi_' . (string) Str::uuid() . '.xlsx';

            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($tmpFile);

            return $tmpFile;
        } catch (\Throwable $e) {
            Log::error('prepareExcelForPdf laporan verifikasi gagal: ' . $e->getMessage(), [
                'file' => $excelPath,
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    private function protectVerificationChannelPageBreaks(Worksheet $sheet, array $bounds, int $rowsPerPage = 72): void
    {
        try {
            $groups = $this->detectVerificationChannelPageGroups($sheet, $bounds);

            if (empty($groups)) {
                return;
            }

            $pageCapacity = $this->verificationPageHeightCapacity($rowsPerPage);
            $currentPageHeight = 0.0;
            $startRow = (int) $bounds['start_row'];

            foreach ($groups as $group) {
                $groupStart = (int) $group['start'];
                $groupHeight = (float) $group['height'];
                $startsNewSection = !empty($group['starts_section']);

                if ($groupHeight <= 0) {
                    continue;
                }

                if ($currentPageHeight > 0 && ($startsNewSection || ($currentPageHeight + $groupHeight) > $pageCapacity)) {
                    /*
                     * PhpSpreadsheet/LibreOffice menaruh row break setelah row ini.
                     * Jadi supaya section/CH berikutnya menjadi awal halaman baru,
                     * break dipasang pada row sebelum group itu.
                     */
                    $breakRow = max($startRow, $groupStart - 1);

                    if ($breakRow >= $startRow) {
                        $sheet->setBreak('A' . $breakRow, Worksheet::BREAK_ROW);
                    }

                    $currentPageHeight = 0.0;
                }

                $currentPageHeight += $groupHeight;
            }
        } catch (\Throwable $e) {
            Log::warning('Gagal menjaga pagination CH laporan verifikasi: ' . $e->getMessage());
        }
    }

    private function verificationPageHeightCapacity(int $rowsPerPage): float
    {
        /*
         * rowsPerPage tetap dipakai sebagai knob kepadatan, tetapi dihitung
         * sebagai tinggi row Excel. Default 72 dibuat sedikit konservatif agar
         * blok CH tidak pecah menjadi sisa 1-3 baris di halaman berikutnya.
         */
        $rowsPerPage = max(45, min(95, $rowsPerPage));

        return max(720.0, min(980.0, ($rowsPerPage - 14) * 15.0));
    }

    private function detectVerificationChannelPageGroups(Worksheet $sheet, array $bounds): array
    {
        $blocks = $this->detectVerificationChannelBlocks($sheet, $bounds);

        if (empty($blocks)) {
            return [];
        }

        $startColIndex = (int) $bounds['start_col_index'];
        $endColIndex = (int) $bounds['end_col_index'];
        $previousEnd = ((int) $bounds['start_row']) - 1;
        $groups = [];

        foreach ($blocks as $block) {
            $blockStart = (int) $block['start'];
            $blockEnd = (int) $block['end'];
            $groupStart = $this->firstPrintableVerificationRow(
                $sheet,
                $previousEnd + 1,
                $blockStart,
                $startColIndex,
                $endColIndex
            ) ?? $blockStart;

            $groups[] = [
                'start' => $groupStart,
                'end' => $blockEnd,
                'height' => $this->verificationRowsHeight($sheet, $groupStart, $blockEnd),
                'starts_section' => $groupStart < $blockStart
                    && $this->isVerificationSectionTitleRow($sheet, $groupStart, $startColIndex, $endColIndex),
            ];

            $previousEnd = $blockEnd;
        }

        return $groups;
    }

    private function detectVerificationChannelBlocks(Worksheet $sheet, array $bounds): array
    {
        $startRow = (int) $bounds['start_row'];
        $endRow = (int) $bounds['end_row'];
        $startColIndex = (int) $bounds['start_col_index'];
        $endColIndex = (int) $bounds['end_col_index'];
        $headerRows = [];

        for ($row = $startRow; $row <= $endRow; $row++) {
            if ($this->isVerificationChannelHeaderRow($sheet, $row, $startColIndex, $endColIndex)) {
                $headerRows[] = $row;
            }
        }

        $blocks = [];
        $headerCount = count($headerRows);

        foreach ($headerRows as $index => $headerRow) {
            $nextBoundary = $endRow + 1;

            if ($index + 1 < $headerCount) {
                $nextBoundary = $headerRows[$index + 1];
            }

            for ($row = $headerRow + 1; $row < $nextBoundary; $row++) {
                if ($this->isVerificationSectionTitleRow($sheet, $row, $startColIndex, $endColIndex)) {
                    $nextBoundary = $row;
                    break;
                }
            }

            $blockEnd = $nextBoundary - 1;

            while ($blockEnd > $headerRow && !$this->verificationRowHasText($sheet, $blockEnd, $startColIndex, $endColIndex)) {
                $blockEnd--;
            }

            $blocks[] = [
                'start' => $headerRow,
                'end' => max($headerRow, $blockEnd),
            ];
        }

        return $blocks;
    }

    private function isVerificationChannelHeaderRow(Worksheet $sheet, int $row, int $startColIndex, int $endColIndex): bool
    {
        foreach ($this->verificationRowTexts($sheet, $row, $startColIndex, $endColIndex) as $text) {
            if (preg_match('/^(?:CH|TACHOMETER)\s*\d+$/i', $text)) {
                return true;
            }
        }

        return false;
    }

    private function isVerificationSectionTitleRow(Worksheet $sheet, int $row, int $startColIndex, int $endColIndex): bool
    {
        foreach ($this->verificationRowTexts($sheet, $row, $startColIndex, $endColIndex) as $text) {
            if (preg_match('/^Verifikasi\b/i', $text)) {
                return true;
            }
        }

        return false;
    }

    private function verificationRowHasText(Worksheet $sheet, int $row, int $startColIndex, int $endColIndex): bool
    {
        return !empty($this->verificationRowTexts($sheet, $row, $startColIndex, $endColIndex));
    }

    private function verificationRowTexts(Worksheet $sheet, int $row, int $startColIndex, int $endColIndex): array
    {
        $texts = [];

        for ($colIndex = $startColIndex; $colIndex <= $endColIndex; $colIndex++) {
            $text = $this->verificationPlainCellText($sheet, $colIndex, $row);

            if ($text !== '') {
                $texts[] = $text;
            }
        }

        return $texts;
    }

    private function firstPrintableVerificationRow(
        Worksheet $sheet,
        int $startRow,
        int $endRow,
        int $startColIndex,
        int $endColIndex
    ): ?int {
        for ($row = $startRow; $row <= $endRow; $row++) {
            if ($this->verificationRowHasText($sheet, $row, $startColIndex, $endColIndex)) {
                return $row;
            }
        }

        return null;
    }

    private function detectVerificationWorksheetBounds(Worksheet $sheet): ?array
    {
        $printAreaBounds = $this->verificationWorksheetPrintAreaBounds($sheet);
        $minRow = $printAreaBounds['start_row'] ?? PHP_INT_MAX;
        $maxRow = $printAreaBounds['end_row'] ?? 0;
        $minColIndex = $printAreaBounds['start_col_index'] ?? PHP_INT_MAX;
        $maxColIndex = $printAreaBounds['end_col_index'] ?? 0;

        foreach ($sheet->getCellCollection()->getCoordinates() as $coord) {
            $cell = $sheet->getCell($coord);

            if (!$this->verificationCellHasPrintableContent($cell)) {
                continue;
            }

            [$col, $row] = Coordinate::coordinateFromString($coord);
            $colIndex = Coordinate::columnIndexFromString($col);

            $minRow = min($minRow, (int) $row);
            $maxRow = max($maxRow, (int) $row);
            $minColIndex = min($minColIndex, $colIndex);
            $maxColIndex = max($maxColIndex, $colIndex);
        }

        $mergeCells = $sheet->getMergeCells();

        foreach (array_keys($mergeCells) as $mergedRange) {
            if (!is_string($mergedRange) || strpos($mergedRange, ':') === false) {
                continue;
            }

            [$start, $end] = explode(':', $mergedRange, 2);

            $topLeftCell = $sheet->getCell($start);

            if (!$this->verificationCellHasPrintableContent($topLeftCell)) {
                continue;
            }

            [$startCol, $startRow] = Coordinate::coordinateFromString($start);
            [$endCol, $endRow] = Coordinate::coordinateFromString($end);

            $startColIndex = Coordinate::columnIndexFromString($startCol);
            $endColIndex = Coordinate::columnIndexFromString($endCol);

            $minRow = min($minRow, (int) $startRow);
            $maxRow = max($maxRow, (int) $endRow);
            $minColIndex = min($minColIndex, $startColIndex);
            $maxColIndex = max($maxColIndex, $endColIndex);
        }

        if (method_exists($sheet, 'getDrawingCollection')) {
            foreach ($sheet->getDrawingCollection() as $drawing) {
                $drawingBounds = $this->verificationDrawingBounds($sheet, $drawing);

                if ($drawingBounds === null) {
                    continue;
                }

                $minRow = min($minRow, $drawingBounds['start_row']);
                $maxRow = max($maxRow, $drawingBounds['end_row']);
                $minColIndex = min($minColIndex, $drawingBounds['start_col_index']);
                $maxColIndex = max($maxColIndex, $drawingBounds['end_col_index']);
            }
        }

        if ($maxRow === 0 || $maxColIndex === 0 || $minRow === PHP_INT_MAX || $minColIndex === PHP_INT_MAX) {
            return null;
        }

        return [
            'start_row' => $minRow,
            'end_row' => $maxRow,
            'start_col_index' => $minColIndex,
            'end_col_index' => $maxColIndex,
            'start_col' => Coordinate::stringFromColumnIndex($minColIndex),
            'end_col' => Coordinate::stringFromColumnIndex($maxColIndex),
        ];
    }

    /**
     * Hitung seluruh area gambar, bukan hanya cell anchor kiri atasnya.
     * Jika print area berakhir sebelum gambar (seperti template A1:I15 dengan
     * gambar di B18:I29), LibreOffice akan menghilangkan gambar dari PDF.
     */
    private function verificationDrawingBounds(Worksheet $sheet, $drawing): ?array
    {
        try {
            $coordinate = (string) $drawing->getCoordinates();

            if ($coordinate === '') {
                return null;
            }

            [$startCol, $startRow] = Coordinate::coordinateFromString($coordinate);
            $startColIndex = Coordinate::columnIndexFromString($startCol);
            $endColIndex = $startColIndex;
            $endRow = (int) $startRow;
            $remainingWidth = max(1, (int) $drawing->getWidth())
                + max(0, method_exists($drawing, 'getOffsetX') ? (int) $drawing->getOffsetX() : 0);
            $remainingHeight = max(1, (int) $drawing->getHeight())
                + max(0, method_exists($drawing, 'getOffsetY') ? (int) $drawing->getOffsetY() : 0);
            $defaultFont = $sheet->getParent()->getDefaultStyle()->getFont();

            while ($remainingWidth > 0 && $endColIndex <= 16384) {
                $columnWidth = (float) $sheet->getColumnDimensionByColumn($endColIndex)->getWidth();

                if ($columnWidth < 0) {
                    $columnWidth = (float) $sheet->getDefaultColumnDimension()->getWidth();
                }

                if ($columnWidth < 0) {
                    $columnWidth = 9.140625;
                }

                $remainingWidth -= max(1, SharedDrawing::cellDimensionToPixels($columnWidth, $defaultFont));

                if ($remainingWidth > 0) {
                    $endColIndex++;
                }
            }

            while ($remainingHeight > 0 && $endRow <= 1048576) {
                $rowHeight = (float) $sheet->getRowDimension($endRow)->getRowHeight();

                if ($rowHeight < 0) {
                    $rowHeight = (float) $sheet->getDefaultRowDimension()->getRowHeight();
                }

                if ($rowHeight < 0) {
                    $rowHeight = 15.0;
                }

                $remainingHeight -= max(1, SharedDrawing::pointsToPixels($rowHeight));

                if ($remainingHeight > 0) {
                    $endRow++;
                }
            }

            return [
                'start_row' => (int) $startRow,
                'end_row' => min(1048576, $endRow),
                'start_col_index' => $startColIndex,
                'end_col_index' => min(16384, $endColIndex),
            ];
        } catch (\Throwable $e) {
            Log::warning('Gagal menghitung batas gambar worksheet laporan verifikasi: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Print area dari workbook dipakai sebagai batas awal. Batas akhirnya tetap
     * diperluas oleh cell berisi dan drawing agar gambar/isi yang diletakkan di
     * luar print area lama tidak hilang saat dikonversi ke PDF.
     */
    private function verificationWorksheetPrintAreaBounds(Worksheet $sheet): ?array
    {
        try {
            $rawPrintArea = trim((string) $sheet->getPageSetup()->getPrintArea());

            if ($rawPrintArea === '') {
                return null;
            }

            $minRow = PHP_INT_MAX;
            $maxRow = 0;
            $minColIndex = PHP_INT_MAX;
            $maxColIndex = 0;

            foreach (explode(',', $rawPrintArea) as $rawRange) {
                $range = trim((string) $rawRange);

                if (strpos($range, '!') !== false) {
                    $range = substr($range, strrpos($range, '!') + 1);
                }

                $range = str_replace(['$', "'"], '', $range);

                if (!preg_match('/^([A-Z]+)(\d+):([A-Z]+)(\d+)$/i', $range, $matches)) {
                    continue;
                }

                $startColIndex = Coordinate::columnIndexFromString(strtoupper($matches[1]));
                $startRow = (int) $matches[2];
                $endColIndex = Coordinate::columnIndexFromString(strtoupper($matches[3]));
                $endRow = (int) $matches[4];

                if ($startRow < 1 || $endRow < 1 || $startColIndex < 1 || $endColIndex < 1) {
                    continue;
                }

                $minRow = min($minRow, $startRow, $endRow);
                $maxRow = max($maxRow, $startRow, $endRow);
                $minColIndex = min($minColIndex, $startColIndex, $endColIndex);
                $maxColIndex = max($maxColIndex, $startColIndex, $endColIndex);
            }

            if ($maxRow === 0 || $maxColIndex === 0 || $minRow === PHP_INT_MAX || $minColIndex === PHP_INT_MAX) {
                return null;
            }

            return [
                'start_row' => $minRow,
                'end_row' => $maxRow,
                'start_col_index' => $minColIndex,
                'end_col_index' => $maxColIndex,
                'start_col' => Coordinate::stringFromColumnIndex($minColIndex),
                'end_col' => Coordinate::stringFromColumnIndex($maxColIndex),
            ];
        } catch (\Throwable $e) {
            Log::warning('Gagal membaca print area worksheet laporan verifikasi: ' . $e->getMessage());

            return null;
        }
    }

    private function verificationCellHasPrintableContent($cell): bool
    {
        try {
            $value = $cell->getValue();

            if ($value instanceof RichText) {
                $value = $value->getPlainText();
            }

            if (is_string($value) && strlen($value) > 0 && substr($value, 0, 1) === '=') {
                $calculatedValue = null;
                $hasCalculatedValue = false;

                try {
                    $calculatedValue = $cell->getCalculatedValue();
                    $hasCalculatedValue = true;
                } catch (\Throwable $e) {
                    $hasCalculatedValue = false;
                }

                if ($hasCalculatedValue) {
                    if ($calculatedValue instanceof RichText) {
                        $calculatedValue = $calculatedValue->getPlainText();
                    }

                    if ($calculatedValue === null) {
                        return false;
                    }

                    if (is_string($calculatedValue) && trim($calculatedValue) === '') {
                        return false;
                    }

                    return true;
                }

                if (method_exists($cell, 'getOldCalculatedValue')) {
                    $oldCalculatedValue = $cell->getOldCalculatedValue();

                    if ($oldCalculatedValue instanceof RichText) {
                        $oldCalculatedValue = $oldCalculatedValue->getPlainText();
                    }

                    if ($oldCalculatedValue !== null && (!is_string($oldCalculatedValue) || trim($oldCalculatedValue) !== '')) {
                        return true;
                    }
                }

                return false;
            }

            if ($value === null) {
                return false;
            }

            if (is_string($value) && trim($value) === '') {
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function verificationRowsHeight(Worksheet $sheet, int $startRow, int $endRow): float
    {
        $height = 0.0;

        for ($row = $startRow; $row <= $endRow; $row++) {
            $rowHeight = (float) $sheet->getRowDimension($row)->getRowHeight();
            $height += $rowHeight > 0 ? $rowHeight : 15.0;
        }

        return $height;
    }

    private function verificationPlainCellText(Worksheet $sheet, int $colIndex, int $row): string
    {
        try {
            $value = $sheet->getCell(Coordinate::stringFromColumnIndex($colIndex) . $row)->getValue();

            if ($value instanceof RichText) {
                $value = $value->getPlainText();
            }

            if ($value === null) {
                return '';
            }

            return trim(preg_replace('/\s+/', ' ', (string) $value));
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function deleteVerificationDirectoryIfExists(string $dir): void
    {
        if (empty($dir) || !is_dir($dir)) {
            return;
        }

        $items = @scandir($dir);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $item;

            if (is_dir($path)) {
                $this->deleteVerificationDirectoryIfExists($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}
