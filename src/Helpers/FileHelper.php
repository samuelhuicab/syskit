<?php
namespace SysKit\Helpers;

use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class FileHelper
{
    /* =========================
     *  COMPRESIÓN / DESCOMPRESIÓN
     * ========================= */

    /** Crea un ZIP desde un archivo o carpeta (recursivo). */
    public static function zip(string $source, string $destination, array $exclude = []): bool
    {
        if (!extension_loaded('zip')) {
            throw new \Exception("La extensión ZIP no está habilitada en PHP.");
        }

        $source = rtrim(realpath($source), DIRECTORY_SEPARATOR);
        if ($source === false) {
            throw new \Exception("Ruta de origen inválida: $source");
        }

        $zip = new ZipArchive();
        if ($zip->open($destination, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \Exception("No se pudo crear el ZIP en: $destination");
        }

        if (is_dir($source)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $file) {
                /** @var \SplFileInfo $file */
                if ($file->isDir()) continue;

                $filePath = $file->getRealPath();
                if (self::isExcluded($filePath, $exclude)) continue;

                $relativePath = substr($filePath, strlen($source) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        } else {
            if (!self::isExcluded($source, $exclude)) {
                $zip->addFile($source, basename($source));
            }
        }

        $zip->close();
        return true;
    }

    /** Extrae un ZIP a un destino (crea carpeta si no existe). */
    public static function unzip(string $zipFile, string $destination): bool
    {
        if (!extension_loaded('zip')) {
            throw new \Exception("La extensión ZIP no está habilitada en PHP.");
        }
        if (!file_exists($zipFile)) {
            throw new \Exception("ZIP no encontrado: $zipFile");
        }
        self::ensureDirectory($destination);

        $zip = new ZipArchive();
        if ($zip->open($zipFile) !== true) {
            throw new \Exception("No se pudo abrir el ZIP: $zipFile");
        }
        if (!$zip->extractTo($destination)) {
            $zip->close();
            throw new \Exception("Error al extraer ZIP en: $destination");
        }
        $zip->close();
        return true;
    }

    /* =========================
     *  ARCHIVOS / CARPETAS
     * ========================= */

    /** Copia archivo/carpeta (recursivo). */
    public static function copy(string $source, string $destination, array $exclude = []): bool
    {
        if (is_dir($source)) {
            self::ensureDirectory($destination);
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($iterator as $item) {
                $targetPath = $destination . DIRECTORY_SEPARATOR . self::relativePath($item->getPathname(), $source);
                if (self::isExcluded($item->getPathname(), $exclude)) continue;

                if ($item->isDir()) {
                    self::ensureDirectory($targetPath);
                } else {
                    self::ensureDirectory(dirname($targetPath));
                    if (!copy($item->getPathname(), $targetPath)) {
                        throw new \Exception("No se pudo copiar: " . $item->getPathname());
                    }
                }
            }
            return true;
        }

        // Archivo único
        self::ensureDirectory(dirname($destination));
        if (!copy($source, $destination)) {
            throw new \Exception("No se pudo copiar: $source");
        }
        return true;
    }

    /** Mueve archivo/carpeta (recursivo). */
    public static function move(string $source, string $destination, array $exclude = []): bool
    {
        self::copy($source, $destination, $exclude);
        self::delete($source);
        return true;
    }

    /** Elimina archivo o carpeta (recursivo). */
    public static function delete(string $path): bool
    {
        if (!file_exists($path)) return true;

        if (is_file($path) || is_link($path)) {
            return @unlink($path);
        }

        $items = array_diff(scandir($path), ['.', '..']);
        foreach ($items as $item) {
            $itemPath = $path . DIRECTORY_SEPARATOR . $item;
            self::delete($itemPath);
        }
        return @rmdir($path);
    }

    /** Crea carpeta si no existe. */
    public static function ensureDirectory(string $dir, int $mode = 0775): void
    {
        if (!is_dir($dir) && !@mkdir($dir, $mode, true) && !is_dir($dir)) {
            throw new \Exception("No se pudo crear el directorio: $dir");
        }
    }

    /** Lista archivos (no carpetas) de una ruta (no recursivo). */
    public static function listFiles(string $path, ?string $pattern = null): array
    {
        if (!is_dir($path)) return [];
        $files = [];
        $dir = opendir($path);
        if ($dir === false) return [];
        while (($f = readdir($dir)) !== false) {
            $full = $path . DIRECTORY_SEPARATOR . $f;
            if (is_file($full)) {
                if ($pattern && !fnmatch($pattern, $f)) continue;
                $files[] = $full;
            }
        }
        closedir($dir);
        sort($files);
        return $files;
    }

    /* =========================
     *  LIMPIEZA / MANTENIMIENTO
     * ========================= */

    /**
     * Elimina archivos más viejos que X días (no recursivo por defecto).
     * $options:
     *   - recursive (bool) por defecto false
     *   - exclude (array) rutas o patrones fnmatch
     *   - simulate (bool) no elimina, solo devuelve conteo
     */
    public static function deleteOldFiles(string $path, int $days, array $options = []): int
    {
        $recursive = (bool)($options['recursive'] ?? false);
        $exclude   = (array)($options['exclude'] ?? []);
        $simulate  = (bool)($options['simulate'] ?? false);

        if (!is_dir($path)) return 0;

        $limitTs = time() - ($days * 86400);
        $count = 0;

        if ($recursive) {
            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($it as $file) {
                if (!$file->isFile()) continue;
                $fp = $file->getPathname();
                if (self::isExcluded($fp, $exclude)) continue;
                if (filemtime($fp) < $limitTs) {
                    if (!$simulate) @unlink($fp);
                    $count++;
                }
            }
        } else {
            foreach (glob($path . '/*') as $fp) {
                if (!is_file($fp)) continue;
                if (self::isExcluded($fp, $exclude)) continue;
                if (filemtime($fp) < $limitTs) {
                    if (!$simulate) @unlink($fp);
                    $count++;
                }
            }
        }

        return $count;
    }

    /* =========================
     *  UTILIDADES
     * ========================= */

    /** Devuelve tamaño legible (e.g., 12.4 MB). */
    public static function humanSize(int $bytes): string
    {
        $units = ['B','KB','MB','GB','TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units)-1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /** Tamaño total (bytes) de una carpeta (recursivo). */
    public static function dirSize(string $path): int
    {
        if (!is_dir($path)) return 0;
        $size = 0;
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($it as $file) {
            if ($file->isFile()) $size += $file->getSize();
        }
        return $size;
    }

    /** Determina si una ruta debe excluirse por coincidencia exacta o patrón fnmatch. */
    private static function isExcluded(string $path, array $exclude): bool
    {
        foreach ($exclude as $rule) {
            // coincidencia por patrón (supports *, ?, [])
            if (fnmatch($rule, basename($path)) || fnmatch($rule, $path)) {
                return true;
            }
            // coincidencia exacta
            if (realpath($path) === realpath($rule)) {
                return true;
            }
        }
        return false;
    }

    /** Devuelve ruta relativa respecto a un base. */
    private static function relativePath(string $absolute, string $base): string
    {
        $base = rtrim(realpath($base), DIRECTORY_SEPARATOR);
        $absolute = realpath($absolute);
        return ltrim(substr($absolute, strlen($base)), DIRECTORY_SEPARATOR);
    }
}
