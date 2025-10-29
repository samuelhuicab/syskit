<?php
namespace SysKit\Helpers;

class SystemHelper
{
    public static function getInfo()
    {
        return [
            'php_version' => PHP_VERSION,
            'os' => PHP_OS,
            'hostname' => gethostname(),
            'ip' => gethostbyname(gethostname()),
            'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2) . ' MB',
            'disk_free' => round(@disk_free_space('/') / 1024 / 1024 / 1024, 2) . ' GB',
            'uptime' => self::getUptime(),
        ];
    }

    public static function getMetrics()
    {
        // Previene error en Windows
        $cpuLoad = function_exists('\\sys_getloadavg') ? \sys_getloadavg() : [0, 0, 0];

        return [
            'cpu_load' => $cpuLoad,
            'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
            'disk_total' => round(@disk_total_space('/') / 1024 / 1024 / 1024, 2) . ' GB',
            'disk_free'  => round(@disk_free_space('/') / 1024 / 1024 / 1024, 2) . ' GB',
            'php_processes' => self::countProcesses('php'),
            'time' => date('Y-m-d H:i:s')
        ];
    }

    private static function getUptime()
    {
        // En Windows, 'uptime -p' no existe, así que devolvemos un valor simulado
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return 'Uptime no disponible en Windows';
        }

        $uptime = @shell_exec('uptime -p');
        return trim($uptime) ?: 'No disponible';
    }

    private static function countProcesses(string $name)
    {
        // En Windows no existe "pgrep", así que devolvemos 1 por defecto
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return 1;
        }

        $output = @shell_exec("pgrep -c $name");
        return (int) trim($output) ?: 0;
    }
}
