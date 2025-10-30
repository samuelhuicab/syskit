<?php
namespace SysKit\Helpers;

/**
 * SysKit ExcelLiteHelper
 *
 * Generador ultraliviano de archivos XLSX a partir de datos ya obtenidos.
 * No consulta la base de datos directamente, solo convierte arrays en Excel.
 * Ideal para millones de filas con encabezado personalizado o autodetectado.
 */
class ExcelLiteHelper
{
    /**
     * Genera un Excel optimizado a partir de datos ya cargados.
     *
     * @param string $filePath Ruta donde guardar el Excel (.xlsx)
     * @param array $data Arreglo de datos (cada elemento = fila asociativa)
     * @param array|null $headers Encabezados opcionales (si no se dan, se usan las llaves del primer registro)
     * @param string $titleBg Color de fondo del encabezado (hex sin #)
     * @param string $titleColor Color del texto del encabezado (hex sin #)
     * @return bool
     */
    public static function exportArray(string $filePath, array $data, ?array $headers = null, string $titleBg = '071E40', string $titleColor = 'FFFFFF'): bool
    {
        if (empty($data)) {
            throw new \Exception("No hay datos para exportar");
        }

        // Si no se pasaron headers, se usan las llaves del primer elemento
        if (!$headers) {
            $headers = array_keys($data[0]);
        }

        $baseDir = dirname($filePath);
        if (!is_dir($baseDir)) mkdir($baseDir, 0777, true);

        // === Crear XML de hoja (streaming) ===
        $sheetPath = $filePath . '.sheet.xml';
        $fh = fopen($sheetPath, 'w');
        fwrite($fh, '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>');

        // Encabezados
        fwrite($fh, '<row>');
        foreach ($headers as $h) {
            fwrite($fh, '<c t="inlineStr" s="1"><is><t>' . htmlspecialchars($h) . '</t></is></c>');
        }
        fwrite($fh, '</row>');

        // Filas de datos
        foreach ($data as $row) {
            fwrite($fh, '<row>');
            foreach ($headers as $key) {
                $value = isset($row[$key]) ? $row[$key] : '';
                fwrite($fh, '<c t="inlineStr"><is><t>' . htmlspecialchars((string)$value) . '</t></is></c>');
            }
            fwrite($fh, '</row>');
        }

        fwrite($fh, '</sheetData></worksheet>');
        fclose($fh);

        // === Estructura interna del XLSX ===
        $root = $filePath . '_dir';
        self::deleteDir($root);
        mkdir($root, 0777, true);
        mkdir("$root/_rels", 0777, true);
        mkdir("$root/xl", 0777, true);
        mkdir("$root/xl/_rels", 0777, true);
        mkdir("$root/xl/worksheets", 0777, true);

        // Copiar hoja generada
        copy($sheetPath, "$root/xl/worksheets/sheet1.xml");
        unlink($sheetPath);

        // styles.xml
        $stylesXml = '<?xml version="1.0" encoding="UTF-8"?>
            <styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
                <fonts count="2">
                    <font><sz val="11"/><color rgb="FF000000"/><name val="Calibri"/></font>
                    <font><sz val="11"/><color rgb="FF' . strtoupper($titleColor) . '"/><name val="Calibri" bold="1"/></font>
                </fonts>
                <fills count="2">
                    <fill><patternFill patternType="none"/></fill>
                    <fill><patternFill patternType="solid"><fgColor rgb="FF' . strtoupper($titleBg) . '"/><bgColor indexed="64"/></patternFill></fill>
                </fills>
                <borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>
                <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
                <cellXfs count="2">
                    <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
                    <xf numFmtId="0" fontId="1" fillId="1" borderId="0" xfId="0" applyFont="1" applyFill="1"/>
                </cellXfs>
            </styleSheet>';
        file_put_contents("$root/xl/styles.xml", $stylesXml);

        // workbook.xml
        $workbookXml = '<?xml version="1.0" encoding="UTF-8"?>
            <workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"
                      xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
                <sheets>
                    <sheet name="Datos" sheetId="1" r:id="rId1"/>
                </sheets>
            </workbook>';
        file_put_contents("$root/xl/workbook.xml", $workbookXml);

        // Relaciones
        $relsXml = '<?xml version="1.0" encoding="UTF-8"?>
            <Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
                <Relationship Id="rId1"
                    Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet"
                    Target="worksheets/sheet1.xml"/>
                <Relationship Id="rId2"
                    Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles"
                    Target="styles.xml"/>
            </Relationships>';
        file_put_contents("$root/_rels/.rels", $relsXml);
        file_put_contents("$root/xl/_rels/workbook.xml.rels", $relsXml);

        // [Content_Types].xml
        $contentTypesXml = '<?xml version="1.0" encoding="UTF-8"?>
            <Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
                <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
                <Default Extension="xml" ContentType="application/xml"/>
                <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
                <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
                <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
            </Types>';
        file_put_contents("$root/[Content_Types].xml", $contentTypesXml);

        // Renombrar carpeta como .xlsx (Excel válido)
        rename($root, $filePath);

        return true;
    }

    /** Limpieza recursiva de carpetas temporales */
    private static function deleteDir(string $dir): void
    {
        if (!is_dir($dir)) return;
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file) : unlink($file);
        }
        rmdir($dir);
    }
}
