<?php
namespace SysKit\Helpers;

/**
 * SysKit SystemHelper v2.0
 *
 * Módulo de diagnóstico y monitoreo del sistema.
 * Proporciona información detallada sobre el entorno PHP, CPU, memoria, disco,
 * red y temperatura del sistema. Compatible con Windows y Linux.
 */
class SystemHelper
{
    /**
     * Devuelve información general del entorno del sistema.
     */
    public static function getInfo(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'os' => PHP_OS,
            'hostname' => gethostname(),
            'ip' => gethostbyname(gethostname()),
            'uptime' => self::getUptime(),
            'server_time' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Devuelve métricas generales del sistema.
     * Incluye uso de CPU, memoria, disco, procesos y temperatura.
     */
    public static function getMetrics(): array
    {
        $cpuLoad = function_exists('\\sys_getloadavg') ? \sys_getloadavg() : [0, 0, 0];

        return [
            'cpu_cores' => self::getCpuCores(),
            'cpu_usage_percent' => self::getCpuUsage(),
            'cpu_load' => $cpuLoad,
            'memory_total' => self::getMemoryTotal(),
            'memory_used_percent' => self::getMemoryUsedPercent(),
            'disk_total' => round(@disk_total_space('/') / 1024 / 1024 / 1024, 2) . ' GB',
            'disk_free'  => round(@disk_free_space('/') / 1024 / 1024 / 1024, 2) . ' GB',
            'php_processes' => self::countProcesses('php'),
            'temperature' => self::getTemperature(),
            'network' => self::getNetworkStats(),
            'time' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Devuelve el tiempo que el sistema lleva encendido.
     */
    private static function getUptime(): string
    {
        if (stripos(PHP_OS, 'WIN') !== false) {
            $output = @shell_exec('net stats srv');
            if ($output && preg_match('/desde (.*)/', $output, $matches)) {
                return trim($matches[1]);
            }
            return 'No disponible en Windows';
        }

        $uptime = @shell_exec('uptime -p');
        return trim($uptime) ?: 'No disponible';
    }

    /**
     * Obtiene la cantidad de núcleos de CPU disponibles.
     */
    private static function getCpuCores(): int
    {
        if (stripos(PHP_OS, 'WIN') !== false) {
            $output = getenv("NUMBER_OF_PROCESSORS");
            return (int) $output ?: 1;
        }

        $output = @shell_exec("nproc 2>/dev/null");
        return (int) trim($output) ?: 1;
    }

    /**
     * Calcula el uso actual de CPU en porcentaje.
     */
    private static function getCpuUsage(): float
    {
        if (stripos(PHP_OS, 'WIN') !== false) {
            $output = @shell_exec('wmic cpu get loadpercentage /value');
            if (preg_match('/LoadPercentage=(\d+)/', $output, $matches)) {
                return (float) $matches[1];
            }
            return 0.0;
        }

        $load = @sys_getloadavg();
        $cores = self::getCpuCores();
        return $cores > 0 ? round(($load[0] / $cores) * 100, 2) : 0.0;
    }

    /**
     * Obtiene la memoria total en MB.
     */
    private static function getMemoryTotal(): string
    {
        if (stripos(PHP_OS, 'WIN') !== false) {
            $output = @shell_exec('wmic computersystem get TotalPhysicalMemory');
            if ($output) {
                $lines = explode("\n", trim($output));
                $bytes = (int) preg_replace('/[^0-9]/', '', end($lines));
                return round($bytes / 1024 / 1024, 2) . ' MB';
            }
            return 'No disponible';
        }

        $memInfo = @file_get_contents('/proc/meminfo');
        if ($memInfo && preg_match('/MemTotal:\s+(\d+)\skB/', $memInfo, $matches)) {
            return round($matches[1] / 1024, 2) . ' MB';
        }

        return 'No disponible';
    }

    /**
     * Calcula el porcentaje de memoria usada.
     */
    private static function getMemoryUsedPercent(): string
    {
        if (stripos(PHP_OS, 'WIN') !== false) {
            $output = @shell_exec('wmic OS get FreePhysicalMemory,TotalVisibleMemorySize /Value');
            if (preg_match_all('/(\w+)=(\d+)/', $output, $matches)) {
                $data = array_combine($matches[1], $matches[2]);
                if (isset($data['FreePhysicalMemory'], $data['TotalVisibleMemorySize'])) {
                    $used = 100 - (($data['FreePhysicalMemory'] / $data['TotalVisibleMemorySize']) * 100);
                    return round($used, 2) . '%';
                }
            }
            return 'No disponible';
        }

        $memInfo = @file_get_contents('/proc/meminfo');
        if ($memInfo &&
            preg_match('/MemTotal:\s+(\d+)\skB/', $memInfo, $total) &&
            preg_match('/MemAvailable:\s+(\d+)\skB/', $memInfo, $avail)) {
            $used = 100 - (($avail[1] / $total[1]) * 100);
            return round($used, 2) . '%';
        }

        return 'No disponible';
    }

    /**
     * Cuenta los procesos activos con un nombre determinado.
     */
    private static function countProcesses(string $name): int
    {
        if (stripos(PHP_OS, 'WIN') !== false) {
            $output = @shell_exec("tasklist | find /c \"$name\"");
            return (int) trim($output) ?: 0;
        }

        $output = @shell_exec("pgrep -c $name");
        return (int) trim($output) ?: 0;
    }

    /**
     * Devuelve la temperatura del sistema si está disponible.
     */
    private static function getTemperature(): string
    {
        if (file_exists('/sys/class/thermal/thermal_zone0/temp')) {
            $temp = (int) file_get_contents('/sys/class/thermal/thermal_zone0/temp');
            return ($temp / 1000) . ' °C';
        }

        $temp = @shell_exec("sensors 2>/dev/null | grep 'temp1' | head -n 1");
        if ($temp && preg_match('/\+([\d\.]+)/', $temp, $matches)) {
            return $matches[1] . ' °C';
        }

        return 'No disponible';
    }

    /**
     * Devuelve información de red básica.
     */
    public static function getNetworkStats(): array
    {
        if (stripos(PHP_OS, 'WIN') !== false) {
            $output = @shell_exec('netstat -e');
            return ['system' => 'Windows', 'raw' => trim($output)];
        }

        $output = @shell_exec("cat /proc/net/dev | grep ':'");
        return ['system' => 'Linux', 'raw' => trim($output)];
    }

    /**
     * Devuelve el estado de salud general del sistema.
     */
    public static function getHealth(): array
    {
        $metrics = self::getMetrics();

        $status = 'OK';
        if ((float)$metrics['cpu_usage_percent'] > 80 || (float)$metrics['memory_used_percent'] > 80) {
            $status = 'HIGH LOAD';
        }

        return [
            'status' => $status,
            'timestamp' => date('Y-m-d H:i:s'),
            'details' => $metrics
        ];
    }

    /**
     * Exporta la información y métricas del sistema en formato JSON o HTML.
     */
    public static function export(string $format = 'json'): string
    {
        $data = array_merge(self::getInfo(), self::getMetrics());
        return $format === 'html'
            ? nl2br(htmlspecialchars(print_r($data, true)))
            : json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Registra el estado del sistema en el LogHelper.
     */
    public static function logSystemStatus(): void
    {
        if (class_exists('SysKit\\Helpers\\LogHelper')) {
            \SysKit\Helpers\LogHelper::info('Estado del sistema', self::getMetrics());
        }
    }
}
