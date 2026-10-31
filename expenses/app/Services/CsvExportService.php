<?php

declare(strict_types=1);

namespace App\Services;

class CsvExportService
{
    /**
     * @param array<int, string> $headers
     * @param array<int, array<int, mixed>> $rows
     */
    public function export(string $filename, array $headers, array $rows): void
    {
        // Set HTTP download headers
        header('Content-Type: text/csv; charset=UTF-8');
        header(sprintf('Content-Disposition: attachment; filename="%s"', $filename));
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'wb');
        if ($out === false) {
            return;
        }

        // Emit UTF-8 BOM for spreadsheet compatibility
        fwrite($out, "\xEF\xBB\xBF");

        // Write header
        fputcsv($out, array_map([$this, 'sanitizeCell'], $headers));

        // Write rows
        foreach ($rows as $row) {
            fputcsv($out, array_map([$this, 'sanitizeCell'], $row));
        }

        fclose($out);
        exit;
    }

    public function sanitizeCell(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $str = (string) $value;

        // Mitigate CSV formula injection
        if (preg_match('/^([=+\-@\t\r])/', $str)) {
            return "'" . $str;
        }

        return $str;
    }
}
