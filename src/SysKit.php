<?php
namespace SysKit;

use SysKit\Helpers\FileHelper;
use SysKit\Helpers\LogHelper;
use SysKit\Helpers\TaskHelper;
use SysKit\Helpers\ImageHelper;
use SysKit\Helpers\SystemHelper;

/**
 * SysKit — Toolkit PHP para automatización del sistema.
 *
 * v1.1.0 — incluye sistema de logs avanzado.
 */
class SysKit
{
    /* =====================================================
     *   BACKUPS Y ARCHIVOS
     * ===================================================== */

    public static function backupFolder(string $source, string $destination, array $exclude = []): bool
    {
        return FileHelper::zip($source, $destination, $exclude);
    }

    public static function restoreBackup(string $zipFile, string $destination): bool
    {
        return FileHelper::unzip($zipFile, $destination);
    }

    public static function deleteOldFiles(string $path, int $days, array $options = []): int
    {
        return FileHelper::deleteOldFiles($path, $days, $options);
    }

    public static function copy(string $source, string $destination, array $exclude = []): bool
    {
        return FileHelper::copy($source, $destination, $exclude);
    }

    public static function move(string $source, string $destination, array $exclude = []): bool
    {
        return FileHelper::move($source, $destination, $exclude);
    }

    public static function delete(string $path): bool
    {
        return FileHelper::delete($path);
    }

    public static function dirSize(string $path, bool $humanReadable = true)
    {
        $size = FileHelper::dirSize($path);
        return $humanReadable ? FileHelper::humanSize($size) : $size;
    }

    /* =====================================================
     *  LOGS AVANZADOS
     * ===================================================== */

    /**
     * Registra un mensaje con nivel, categoría y contexto opcional.
     *
     * @param string $message Texto del log
     * @param string $level info|warning|error|debug
     * @param string|null $category Subcarpeta opcional (e.g. "api", "backup")
     * @param array $context Datos adicionales ['user'=>'sam','ip'=>'127.0.0.1']
     */
    public static function log(string $message, string $level = 'info', ?string $category = null, array $context = []): void
    {
        LogHelper::write($message, $level, $category, $context);
    }

    /** Cambia la ruta base donde se guardan los logs */
    public static function setLogPath(string $path): void
    {
        LogHelper::setBasePath($path);
    }

    /** Define los días de retención de logs (por defecto: 30) */
    public static function setLogRetention(int $days): void
    {
        LogHelper::setRetention($days);
    }

    /** Obtiene logs recientes en array (para paneles o APIs) */
    public static function getLogs(?string $category = null, int $limit = 100): array
    {
        return LogHelper::getLogs($category, $limit);
    }

    /* =====================================================
     *  TAREAS AUTOMÁTICAS
     * ===================================================== */

    public static function runEvery(string $interval, callable $callback, string $key = 'default', int $maxRuns = 0): void
    {
        TaskHelper::runEvery($interval, $callback, $key, $maxRuns);
    }

    /* =====================================================
     *  IMÁGENES
     * ===================================================== */

    public static function optimizeImage(string $path, int $quality = 80): bool
    {
        return ImageHelper::optimize($path, $quality);
    }

    /* =====================================================
     *  SISTEMA
     * ===================================================== */

    public static function info(): array
    {
        return SystemHelper::getInfo();
    }

    public static function monitor(bool $asJson = true)
    {
        $data = SystemHelper::getMetrics();
        return $asJson ? json_encode($data, JSON_PRETTY_PRINT) : $data;
    }

    /* =====================================================
     *  UTILIDADES
     * ===================================================== */

    public static function ensureDirectory(string $dir, int $mode = 0775): void
    {
        FileHelper::ensureDirectory($dir, $mode);
    }

    public static function listFiles(string $path, ?string $pattern = null): array
    {
        return FileHelper::listFiles($path, $pattern);
    }
}
