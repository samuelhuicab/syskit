<?php
namespace SysKit\Helpers;

use SysKit\SysKit;

class TaskHelper
{
    public static function runEvery(string $interval, callable $callback, string $key = 'default', int $maxRuns = 0): void
    {
        $storage = sys_get_temp_dir() . '/syskit_tasks.json';
        $data = file_exists($storage) ? json_decode(file_get_contents($storage), true) : [];

        $lastRun = isset($data[$key]) ? strtotime($data[$key]['lastRun']) : 0;
        $runs = $data[$key]['count'] ?? 0;
        $now = time();
        $seconds = self::parseInterval($interval);

        if ($maxRuns > 0 && $runs >= $maxRuns) {
            SysKit::log("Tarea '$key' alcanzó el máximo de ejecuciones ($maxRuns)", 'warning');
            return;
        }

        if ($now - $lastRun >= $seconds) {
            $callback();
            $data[$key] = ['lastRun' => date('Y-m-d H:i:s'), 'count' => $runs + 1];
            file_put_contents($storage, json_encode($data, JSON_PRETTY_PRINT));
            SysKit::log("Tarea '$key' ejecutada automáticamente (cada $interval)", 'info');
        }
    }

    private static function parseInterval(string $interval): int
    {
        [$value, $unit] = explode(' ', trim($interval), 2);
        $value = (int)$value;
        return match(strtolower($unit)) {
            'second', 'seconds' => $value,
            'minute', 'minutes' => $value * 60,
            'hour', 'hours'     => $value * 3600,
            'day', 'days'       => $value * 86400,
            default => throw new \Exception("Intervalo inválido: $interval")
        };
    }
}
