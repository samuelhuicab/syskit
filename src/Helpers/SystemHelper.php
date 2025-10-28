<?php
namespace SysKit\Helpers;

class SystemHelper
{
    public static function getInfo(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'os' => PHP_OS,
            'hostname' => gethostname(),
            'ip' => gethostbyname(gethostname()),
            'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2) . ' MB',
            'disk_free' => round(disk_free_space('/') / 1024 / 1024 / 1024, 2) . ' GB',
            'uptime' => self::getUptime(),
        ];
    }

    public static function getMetrics(): array
    {
        return [
            'cpu_load' => sys_getloadavg(),
            'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
            'disk_total' => round(disk_total_space('/') / 1024 / 1024 / 1024, 2) . ' GB',
            'disk_free' => round(disk_free_space('/') / 1024 / 1024 / 1024, 2) . ' GB',
            'php_processes' => self::countProcesses('php'),
            'time' => date('Y-m-d H:i:s')
        ];
    }

    private static function getUptime(): string
    {
        $uptime = @shell_exec('uptime -p');
        return trim($uptime) ?: 'No disponible';
    }

    private static function countProcesses(string $name): int
    {
        $output = @shell_exec("pgrep -c $name");
        return (int) trim($output) ?: 0;
    }
}
