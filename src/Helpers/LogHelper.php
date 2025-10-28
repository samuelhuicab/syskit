<?php
namespace SysKit\Helpers;

class LogHelper
{
    public static function write(string $message, string $level = 'info', string $file = 'syskit.log'): void
    {
        $timestamp = date('[Y-m-d H:i:s]');
        $line = "$timestamp [$level] $message\n";
        file_put_contents($file, $line, FILE_APPEND);
    }
}
