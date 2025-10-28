<?php
namespace SysKit\Helpers;

class LogHelper
{
    private static string $basePath = __DIR__ . '/../../logs';
    private static int $retentionDays = 30;

    public static function write(string $message, string $level = 'info', ?string $category = null, array $context = []): void
    {
        $folder = self::$basePath . ($category ? "/$category" : '');
        if (!is_dir($folder)) mkdir($folder, 0775, true);

        $file = "$folder/" . date('Y-m-d') . ".log";
        $timestamp = date('[Y-m-d H:i:s]');
        $ctx = empty($context) ? '' : json_encode($context, JSON_UNESCAPED_UNICODE);
        $line = "$timestamp [$level] $message $ctx\n";

        file_put_contents($file, $line, FILE_APPEND);

        self::rotate($folder);
        if (PHP_SAPI === 'cli') self::cliOutput($message, $level);
    }

    private static function rotate(string $folder): void
    {
        foreach (glob("$folder/*.log") as $file) {
            if (filemtime($file) < strtotime('-' . self::$retentionDays . ' days')) {
                @unlink($file);
            }
        }
    }

    private static function cliOutput(string $message, string $level): void
    {
        $colors = [
            'info' => "\033[0;32m",
            'warning' => "\033[1;33m",
            'error' => "\033[0;31m",
            'debug' => "\033[0;36m",
        ];
        $reset = "\033[0m";
        $color = $colors[$level] ?? "\033[0;37m";
        echo "{$color}[$level] $message{$reset}\n";
    }

    public static function setBasePath(string $path): void
    {
        self::$basePath = rtrim($path, '/');
    }

    public static function setRetention(int $days): void
    {
        self::$retentionDays = $days;
    }

    public static function getLogs(?string $category = null, int $limit = 100): array
    {
        $folder = self::$basePath . ($category ? "/$category" : '');
        $file = "$folder/" . date('Y-m-d') . ".log";
        if (!file_exists($file)) return [];

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $logs = [];
        foreach (array_slice(array_reverse($lines), 0, $limit) as $line) {
            preg_match('/\[(.*?)\] \[(.*?)\] (.*?)(\{.*\})?$/', $line, $matches);
            $logs[] = [
                'time' => $matches[1] ?? '',
                'level' => $matches[2] ?? '',
                'message' => trim($matches[3] ?? ''),
                'context' => isset($matches[4]) ? json_decode($matches[4], true) : []
            ];
        }
        return $logs;
    }
}
