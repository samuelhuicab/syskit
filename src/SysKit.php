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
 * Incluye funciones de respaldo, limpieza, logs, optimización de imágenes,
 * ejecución de tareas periódicas (cron simulado) y monitoreo del sistema.
 *
 * Autor: Samuel Huicab Pastrana
 * Licencia: MIT
 */
class SysKit
{
    /* =====================================================
     *  BACKUPS Y ARCHIVOS
     * ===================================================== */

    /** Crea un respaldo ZIP de una carpeta o archivo. */
    public static function backupFolder(string $source, string $destination, array $exclude = []): bool
    {
        return FileHelper::zip($source, $destination, $exclude);
    }

    /** Extrae un archivo ZIP. */
    public static function restoreBackup(string $zipFile, string $destination): bool
    {
        return FileHelper::unzip($zipFile, $destination);
    }

    /** Elimina archivos viejos (por días). */
    public static function deleteOldFiles(string $path, int $days, array $options = []): int
    {
        return FileHelper::deleteOldFiles($path, $days, $options);
    }

    /** Copia archivos o carpetas completas. */
    public static function copy(string $source, string $destination, array $exclude = []): bool
    {
        return FileHelper::copy($source, $destination, $exclude);
    }

    /** Mueve archivos o carpetas completas. */
    public static function move(string $source, string $destination, array $exclude = []): bool
    {
        return FileHelper::move($source, $destination, $exclude);
    }

    /** Elimina un archivo o carpeta recursivamente. */
    public static function delete(string $path): bool
    {
        return FileHelper::delete($path);
    }

    /** Devuelve el tamaño legible de una carpeta o archivo. */
    public static function dirSize(string $path, bool $humanReadable = true)
    {
        $size = FileHelper::dirSize($path);
        return $humanReadable ? FileHelper::humanSize($size) : $size;
    }

    /* =====================================================
     *  LOGS
     * ===================================================== */

    /** Registra un mensaje con nivel de log (info, error, warning). */
    public static function log(string $message, string $level = 'info', string $file = 'syskit.log'): void
    {
        LogHelper::write($message, $level, $file);
    }

    /* =====================================================
     *  TAREAS AUTOMÁTICAS (CRON SIMULADO)
     * ===================================================== */

    /**
     * Ejecuta una función cada cierto intervalo (simula cron).
     *
     * @param string $interval Ej. '5 minutes', '1 hour'
     * @param callable $callback Código a ejecutar
     * @param string $key Identificador único
     * @param int $maxRuns Número máximo de ejecuciones (0 = infinito)
     */
    public static function runEvery(string $interval, callable $callback, string $key = 'default', int $maxRuns = 0): void
    {
        TaskHelper::runEvery($interval, $callback, $key, $maxRuns);
    }

    /* =====================================================
     *  IMÁGENES
     * ===================================================== */

    /** Optimiza imagen (JPG, PNG o WEBP). */
    public static function optimizeImage(string $path, int $quality = 80): bool
    {
        return ImageHelper::optimize($path, $quality);
    }

    /* =====================================================
     *  SISTEMA
     * ===================================================== */

    /** Devuelve información del entorno PHP y sistema operativo. */
    public static function info(): array
    {
        return SystemHelper::getInfo();
    }

    /** Devuelve métricas en formato JSON: CPU, RAM, disco, uptime, etc. */
    public static function monitor(bool $asJson = true)
    {
        $data = SystemHelper::getMetrics();
        return $asJson ? json_encode($data, JSON_PRETTY_PRINT) : $data;
    }

    /* =====================================================
     *  UTILIDADES
     * ===================================================== */

    /** Crea una carpeta si no existe. */
    public static function ensureDirectory(string $dir, int $mode = 0775): void
    {
        FileHelper::ensureDirectory($dir, $mode);
    }

    /** Lista archivos dentro de una carpeta (opcional patrón). */
    public static function listFiles(string $path, ?string $pattern = null): array
    {
        return FileHelper::listFiles($path, $pattern);
    }
}
