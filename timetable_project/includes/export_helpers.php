<?php
/**
 * Shared export helpers.
 * Builds Excel, PDF, and print responses from consistent row/column definitions.
 */

function exportColumnValue(array $row, array $column)
{
    if (isset($column['value']) && is_callable($column['value'])) {
        return $column['value']($row);
    }

    $field = $column['field'] ?? null;

    if ($field === null) {
        return '';
    }

    return $row[$field] ?? '';
}

function exportRows($rows): array
{
    if ($rows instanceof mysqli_result) {
        if ($rows->num_rows > 0) {
            $rows->data_seek(0);
        }

        $data = [];

        while ($row = $rows->fetch_assoc()) {
            $data[] = $row;
        }

        return $data;
    }

    if (is_array($rows)) {
        return $rows;
    }

    if ($rows instanceof Traversable) {
        return iterator_to_array($rows);
    }

    return [];
}

function cleanExportCell($value): string
{
    $value = (string) $value;
    $value = str_replace(["\t", "\r"], ' ', $value);
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
    $value = preg_replace('/[ ]+/', ' ', $value);

    return trim($value);
}

function exportFilename(string $filename, string $extension): string
{
    $filename = trim($filename) ?: 'export';
    $filename = preg_replace('/\.[^.]+$/', '', $filename);
    $filename = preg_replace('/[^A-Za-z0-9._-]+/', '_', $filename);
    $filename = trim($filename, '._-') ?: 'export';

    return $filename . '.' . $extension;
}

function exportTableData(array $columns, array $rows): array
{
    $table = [array_map(fn($column) => (string) ($column['label'] ?? ''), $columns)];

    foreach ($rows as $row) {
        $tableRow = [];

        foreach ($columns as $column) {
            $tableRow[] = cleanExportCell(exportColumnValue($row, $column));
        }

        $table[] = $tableRow;
    }

    return $table;
}

function excelColumnName(int $index): string
{
    $name = '';

    while ($index > 0) {
        $index--;
        $name = chr(65 + ($index % 26)) . $name;
        $index = intdiv($index, 26);
    }

    return $name;
}

function clearExportOutputBuffer(): void
{
    while (ob_get_level() > 0) {
        if (!@ob_end_clean()) {
            break;
        }
    }
}

function renderExcelHtmlExport(string $filename, array $columns, array $rows): void
{
    $table = exportTableData($columns, $rows);
    $filename = exportFilename($filename, 'xls');

    clearExportOutputBuffer();
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    echo "\xEF\xBB\xBF";
    ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table {
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #999;
            padding: 6px;
            vertical-align: top;
        }

        th {
            font-weight: bold;
            background: #e5e7eb;
        }
    </style>
</head>
<body>
    <table>
        <?php foreach ($table as $rowIndex => $row): ?>
            <tr>
                <?php foreach ($row as $cell): ?>
                    <?php if ($rowIndex === 0): ?>
                        <th><?= htmlspecialchars((string) $cell, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></th>
                    <?php else: ?>
                        <td><?= htmlspecialchars((string) $cell, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
    <?php
}

function renderExcelExport(string $filename, array $columns, array $rows): void
{
    if (!class_exists('ZipArchive')) {
        renderExcelHtmlExport($filename, $columns, $rows);
        return;
    }

    $table = exportTableData($columns, $rows);
    $filename = exportFilename($filename, 'xlsx');
    $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx_');
    $zip = new ZipArchive();

    if ($tmpFile === false || $zip->open($tmpFile, ZipArchive::OVERWRITE) !== true) {
        http_response_code(500);
        echo 'Unable to create Excel export.';
        return;
    }

    $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
        . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
        . '<sheetData>';

    foreach ($table as $rowIndex => $row) {
        $excelRow = $rowIndex + 1;
        $sheetXml .= '<row r="' . $excelRow . '">';

        foreach ($row as $columnIndex => $value) {
            $cell = excelColumnName($columnIndex + 1) . $excelRow;
            $style = $rowIndex === 0 ? ' s="1"' : '';
            $sheetXml .= '<c r="' . $cell . '" t="inlineStr"' . $style . '><is><t>'
                . htmlspecialchars((string) $value, ENT_XML1 | ENT_COMPAT | ENT_SUBSTITUTE, 'UTF-8')
                . '</t></is></c>';
        }

        $sheetXml .= '</row>';
    }

    $sheetXml .= '</sheetData><autoFilter ref="A1:' . excelColumnName(max(1, count($columns))) . max(1, count($table)) . '"/></worksheet>';

    $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
<Default Extension="xml" ContentType="application/xml"/>
<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>');
    $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>');
    $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
<sheets><sheet name="Export" sheetId="1" r:id="rId1"/></sheets>
</workbook>');
    $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>');
    $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>
<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>
<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>
<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0"/></cellXfs>
</styleSheet>');
    $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
    $zip->close();

    clearExportOutputBuffer();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($tmpFile));
    readfile($tmpFile);
    unlink($tmpFile);
}

function pdfText(string $text): string
{
    $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);

    if ($converted === false) {
        $converted = preg_replace('/[^\x20-\x7E]/', '', $text);
    }

    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $converted);
}

function pdfWrapLines(string $text, float $width, int $fontSize): array
{
    $maxChars = max(3, (int) floor($width / ($fontSize * 0.46)));
    $text = str_replace(' | ', "\n", cleanExportCell($text));
    $paragraphs = preg_split('/\n+/', $text);
    $lines = [];

    foreach ($paragraphs as $paragraph) {
        $paragraph = trim($paragraph);

        if ($paragraph === '') {
            $lines[] = '';
            continue;
        }

        $words = preg_split('/\s+/', $paragraph);
        $line = '';

        foreach ($words as $word) {
            while (mb_strlen($word) > $maxChars) {
                if ($line !== '') {
                    $lines[] = $line;
                    $line = '';
                }

                $lines[] = mb_substr($word, 0, $maxChars);
                $word = mb_substr($word, $maxChars);
            }

            $candidate = $line === '' ? $word : $line . ' ' . $word;

            if (mb_strlen($candidate) <= $maxChars) {
                $line = $candidate;
            } else {
                $lines[] = $line;
                $line = $word;
            }
        }

        if ($line !== '') {
            $lines[] = $line;
        }
    }

    return $lines ?: [''];
}

function pdfDrawTextLines(array $lines, float $x, float $y, int $fontSize, float $lineHeight): string
{
    $content = '';

    foreach ($lines as $index => $line) {
        $content .= 'BT /F1 ' . $fontSize . ' Tf ' . $x . ' ' . ($y - ($index * $lineHeight)) . ' Td (' . pdfText($line) . ") Tj ET\n";
    }

    return $content;
}

function buildPdfDocument(string $title, array $columns, array $rows): string
{
    $table = exportTableData($columns, $rows);
    $pageWidth = 842;
    $pageHeight = 595;
    $margin = 24;
    $titleSize = 14;
    $fontSize = count($columns) > 10 ? 5 : 7;
    $lineHeight = $fontSize + 2;
    $minRowHeight = 22;
    $tableTop = $pageHeight - 72;
    $tableBottom = $margin + 20;
    $availableWidth = $pageWidth - ($margin * 2);
    $columnWidth = $availableWidth / max(1, count($columns));
    $dataRows = array_slice($table, 1);
    $headerCells = [];
    $headerLineCount = 1;

    foreach ($table[0] as $heading) {
        $lines = pdfWrapLines((string) $heading, $columnWidth - 6, $fontSize);
        $headerCells[] = $lines;
        $headerLineCount = max($headerLineCount, count($lines));
    }

    $headerHeight = max(22, ($headerLineCount * $lineHeight) + 10);
    $pages = [];
    $pageRows = [];
    $remainingHeight = $tableTop - $tableBottom - $headerHeight;

    foreach ($dataRows as $row) {
        $cellLines = [];
        $rowLineCount = 1;

        foreach ($row as $value) {
            $lines = pdfWrapLines((string) $value, $columnWidth - 6, $fontSize);
            $cellLines[] = $lines;
            $rowLineCount = max($rowLineCount, count($lines));
        }

        $rowHeight = max($minRowHeight, ($rowLineCount * $lineHeight) + 10);

        if ($pageRows && $remainingHeight - $rowHeight < 0) {
            $pages[] = $pageRows;
            $pageRows = [];
            $remainingHeight = $tableTop - $tableBottom - $headerHeight;
        }

        $pageRows[] = [
            'lines' => $cellLines,
            'height' => min($rowHeight, $tableTop - $tableBottom - $headerHeight),
        ];
        $remainingHeight -= $rowHeight;
    }

    if (empty($pages)) {
        $pages[] = $pageRows;
    } elseif (!empty($pageRows)) {
        $pages[] = $pageRows;
    }

    $streams = [];

    foreach ($pages as $pageIndex => $pageRows) {
        $content = "BT /F1 {$titleSize} Tf {$margin} " . ($pageHeight - 36) . ' Td (' . pdfText($title) . ") Tj ET\n";
        $content .= "BT /F1 8 Tf {$margin} " . ($pageHeight - 52) . ' Td (Page ' . ($pageIndex + 1) . ' of ' . count($pages) . ") Tj ET\n";
        $y = $tableTop;

        $content .= "0.90 g {$margin} " . ($y - $headerHeight + 4) . " {$availableWidth} {$headerHeight} re f 0 g\n";

        foreach ($headerCells as $columnIndex => $headingLines) {
            $x = $margin + ($columnIndex * $columnWidth);
            $content .= "{$x} " . ($y - $headerHeight + 4) . " {$columnWidth} {$headerHeight} re S\n";
            $content .= pdfDrawTextLines($headingLines, $x + 3, $y - 11, $fontSize, $lineHeight);
        }

        $y -= $headerHeight;

        foreach ($pageRows as $rowData) {
            $rowHeight = $rowData['height'];

            foreach ($rowData['lines'] as $columnIndex => $lines) {
                $x = $margin + ($columnIndex * $columnWidth);
                $content .= "{$x} " . ($y - $rowHeight) . " {$columnWidth} {$rowHeight} re S\n";
                $content .= pdfDrawTextLines($lines, $x + 3, $y - 12, $fontSize, $lineHeight);
            }

            $y -= $rowHeight;
        }

        $streams[] = $content;
    }

    $objects = [
        1 => '<< /Type /Catalog /Pages 2 0 R >>',
        3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
    ];

    $kids = [];
    $objectId = 4;

    foreach ($streams as $stream) {
        $pageId = $objectId++;
        $contentId = $objectId++;
        $kids[] = "{$pageId} 0 R";
        $objects[$pageId] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 {$pageWidth} {$pageHeight}] /Resources << /Font << /F1 3 0 R >> >> /Contents {$contentId} 0 R >>";
        $objects[$contentId] = "<< /Length " . strlen($stream) . " >>\nstream\n{$stream}endstream";
    }

    $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($kids) . ' >>';
    ksort($objects);

    $pdf = "%PDF-1.4\n";
    $offsets = [0 => 0];

    foreach ($objects as $id => $object) {
        $offsets[$id] = strlen($pdf);
        $pdf .= "{$id} 0 obj\n{$object}\nendobj\n";
    }

    $xrefOffset = strlen($pdf);
    $pdf .= "xref\n0 " . (max(array_keys($objects)) + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";

    for ($i = 1; $i <= max(array_keys($objects)); $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i] ?? 0);
    }

    $pdf .= "trailer\n<< /Size " . (max(array_keys($objects)) + 1) . " /Root 1 0 R >>\n";
    $pdf .= "startxref\n{$xrefOffset}\n%%EOF";

    return $pdf;
}

function renderPdfExport(string $title, string $filename, array $columns, array $rows): void
{
    $filename = exportFilename($filename, 'pdf');
    $pdf = buildPdfDocument($title, $columns, $rows);

    clearExportOutputBuffer();
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($pdf));
    echo $pdf;
}

function renderPrintableExport(string $title, array $columns, array $rows): void
{
    ?>
<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($title) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 6px;
            text-align: center;
        }

        th {
            background: #e5e5e5;
        }

        @media print {
            body {
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <h2><?= htmlspecialchars($title) ?></h2>

    <table>
        <thead>
            <tr>
                <?php foreach ($columns as $column): ?>
                    <th><?= htmlspecialchars($column['label']) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <?php foreach ($columns as $column): ?>
                        <td><?= htmlspecialchars((string) exportColumnValue($row, $column)) ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <script>
    window.onload = function(){
        window.print();
    };
    </script>
</body>
</html>
    <?php
}

function handleTableExport(array $options): void
{
    $export = $_GET[$options['param'] ?? 'export'] ?? null;

    if ($export === null && isset($options['print_param']) && isset($_GET[$options['print_param']])) {
        $export = 'print';
    }

    if (!in_array($export, ['excel', 'pdf', 'print'], true)) {
        return;
    }

    $rows = $options['rows'];

    if (is_callable($rows)) {
        $rows = $rows();
    }

    $rows = exportRows($rows);
    $columns = $options['columns'];
    $title = $options['title'] ?? 'Export';
    $filename = $options['filename'] ?? 'export.xlsx';

    if ($export === 'excel') {
        renderExcelExport($filename, $columns, $rows);
    } elseif ($export === 'pdf') {
        renderPdfExport($title, $filename, $columns, $rows);
    } else {
        renderPrintableExport($title, $columns, $rows);
    }

    exit();
}

function exportUrl(string $type, array $params = [], string $paramName = 'export'): string
{
    return '?' . http_build_query(array_merge($params, [$paramName => $type]));
}
