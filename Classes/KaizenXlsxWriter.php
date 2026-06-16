<?php

/**
 * Generador mínimo de archivos .xlsx (Open XML) sin dependencias externas.
 */
class KaizenXlsxWriter
{
    /** @var array<int, array<int, string|int|float>> */
    private array $rows = [];

    /** @param array<int, string|int|float|null> $cells */
    public function addRow(array $cells): void
    {
        $this->rows[] = array_map(static function ($value) {
            if ($value === null) {
                return '';
            }
            return $value;
        }, $cells);
    }

    public function save(string $path): bool
    {
        $handle = fopen($path, 'wb');
        if ($handle === false) {
            return false;
        }
        $ok = $this->writeToStream($handle);
        fclose($handle);
        return $ok;
    }

    public function writeToStream($handle): bool
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('La extensión ZipArchive no está disponible en PHP');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'kaizen_xlsx_');
        if ($tmp === false) {
            return false;
        }

        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($tmp);
            return false;
        }

        $zip->addFromString('[Content_Types].xml', $this->xmlContentTypes());
        $zip->addFromString('_rels/.rels', $this->xmlRootRels());
        $zip->addFromString('xl/workbook.xml', $this->xmlWorkbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->xmlWorkbookRels());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->xmlSheet());
        $zip->addFromString('xl/styles.xml', $this->xmlStyles());
        $zip->close();

        $input = fopen($tmp, 'rb');
        if ($input === false) {
            @unlink($tmp);
            return false;
        }

        while (!feof($input)) {
            $chunk = fread($input, 8192);
            if ($chunk === false) {
                fclose($input);
                @unlink($tmp);
                return false;
            }
            if ($chunk !== '' && fwrite($handle, $chunk) === false) {
                fclose($input);
                @unlink($tmp);
                return false;
            }
        }

        fclose($input);
        @unlink($tmp);
        return true;
    }

    private function xmlContentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';
    }

    private function xmlRootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private function xmlWorkbook(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Reporte Kaizen" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private function xmlWorkbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    private function xmlStyles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="1"><fill><patternFill patternType="none"/></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs>'
            . '</styleSheet>';
    }

    private function xmlSheet(): string
    {
        $rowsXml = '';
        foreach ($this->rows as $rowIndex => $row) {
            $rowNum = $rowIndex + 1;
            $cellsXml = '';
            foreach ($row as $colIndex => $value) {
                $ref = $this->columnLetter($colIndex) . $rowNum;
                if (is_int($value) || is_float($value)) {
                    $cellsXml .= '<c r="' . $ref . '"><v>' . $value . '</v></c>';
                    continue;
                }
                $text = $this->escapeXml((string) $value);
                $cellsXml .= '<c r="' . $ref . '" t="inlineStr"><is><t>' . $text . '</t></is></c>';
            }
            $rowsXml .= '<row r="' . $rowNum . '">' . $cellsXml . '</row>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetData>' . $rowsXml . '</sheetData>'
            . '</worksheet>';
    }

    private function columnLetter(int $index): string
    {
        $index++;
        $letters = '';
        while ($index > 0) {
            $index--;
            $letters = chr(65 + ($index % 26)) . $letters;
            $index = intdiv($index, 26);
        }
        return $letters;
    }

    private function escapeXml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
