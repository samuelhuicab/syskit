<?php
namespace SysKit\Helpers;

/**
 * SysKit LogHelper v2.1
 *
 * Implementación de un sistema de logging ligero y extensible sin dependencias externas.
 * Permite registrar eventos con distintos niveles, formateo JSON opcional, rotación automática
 * de archivos de log, envío de alertas por Webhook, modo de buffer, y captura automática
 * de errores y excepciones de PHP.
 */
class LogHelper
{
    /** Directorio base donde se almacenarán los archivos de log */
    private static string $basePath = __DIR__ . '/../../logs';

    /** Días de retención de los archivos de log antes de ser eliminados */
    private static int $retentionDays = 30;

    /** Determina si los mensajes se mostrarán también en la consola (CLI) */
    private static bool $showInConsole = true;

    /** Define si los registros se guardarán en formato JSON */
    private static bool $jsonFormat = false;

    /** URL opcional para enviar alertas por HTTP (errores o eventos críticos) */
    private static ?string $webhookUrl = null;

    /** Indica si se está utilizando el modo buffer (acumular logs en memoria antes de escribirlos) */
    private static bool $bufferMode = false;

    /** Contenedor temporal de logs acumulados en modo buffer */
    private static array $buffer = [];

    /** Niveles de log permitidos */
    private static array $levels = [
        'debug', 'info', 'success', 'warning', 'error', 'critical'
    ];

    /**
     * Registra un nuevo evento de log.
     * Si el modo buffer está activado, se acumula en memoria en lugar de escribirse de inmediato.
     */
    public static function write(string $message, string $level = 'info', ?string $category = null, array $context = []): void
    {
        $level = strtolower($level);
        if (!in_array($level, self::$levels)) $level = 'info';

        $entry = [
            'time' => date('Y-m-d H:i:s'),
            'level' => $level,
            'message' => self::interpolate($message, $context),
            'context' => $context
        ];

        if (self::$bufferMode) {
            self::$buffer[] = $entry;
            return;
        }

        self::store($entry, $category);
    }

    /**
     * Almacena físicamente un registro en el archivo de log correspondiente.
     * También gestiona la rotación y el envío de alertas por Webhook.
     */
    private static function store(array $entry, ?string $category = null): void
    {
        $folder = self::$basePath . ($category ? "/$category" : '');
        if (!is_dir($folder)) mkdir($folder, 0775, true);

        $file = "$folder/" . date('Y-m-d') . ".log";

        // Formato del registro según configuración (texto plano o JSON)
        $line = self::$jsonFormat
            ? json_encode($entry, JSON_UNESCAPED_UNICODE) . "\n"
            : sprintf("[%s] [%s] %s %s\n", $entry['time'], $entry['level'], $entry['message'], json_encode($entry['context']));

        file_put_contents($file, $line, FILE_APPEND);
        self::rotate($folder);

        // Mostrar en consola (si aplica)
        if (self::$showInConsole && PHP_SAPI === 'cli') {
            self::cliOutput($entry['message'], $entry['level']);
        }

        // Enviar alertas críticas a un Webhook (si está configurado)
        if (self::$webhookUrl && in_array($entry['level'], ['error', 'critical'])) {
            self::sendWebhook($entry);
        }
    }

    /**
     * Sustituye las variables en el mensaje por los valores del contexto.
     * Ejemplo: "Usuario {name}" con ['name' => 'Sam'] se convierte en "Usuario Sam".
     */
    private static function interpolate(string $message, array $context): string
    {
        $replace = [];
        foreach ($context as $key => $val) {
            $replace['{' . $key . '}'] = is_scalar($val) ? $val : json_encode($val);
        }
        return strtr($message, $replace);
    }

    /**
     * Elimina los archivos de log antiguos según el número de días de retención configurados.
     */
    private static function rotate(string $folder): void
    {
        foreach (glob("$folder/*.log") as $file) {
            if (filemtime($file) < strtotime('-' . self::$retentionDays . ' days')) {
                @unlink($file);
            }
        }
    }

    /**
     * Imprime un mensaje en la consola con colores según el nivel del log.
     * Solo se aplica cuando se ejecuta en entorno CLI.
     */
    private static function cliOutput(string $message, string $level): void
    {
        $colors = [
            'debug' => "\033[0;36m",
            'info' => "\033[0;37m",
            'success' => "\033[0;32m",
            'warning' => "\033[1;33m",
            'error' => "\033[0;31m",
            'critical' => "\033[1;41m"
        ];
        $reset = "\033[0m";
        $color = $colors[$level] ?? "\033[0m";
        echo "{$color}[$level] {$message}{$reset}\n";
    }

    /**
     * Envía una alerta HTTP POST al Webhook configurado para eventos críticos o de error.
     */
    private static function sendWebhook(array $entry): void
    {
        @file_get_contents(self::$webhookUrl, false, stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json",
                'content' => json_encode($entry, JSON_UNESCAPED_UNICODE)
            ]
        ]));
    }

    /** ---------------------- CONFIGURACIÓN ---------------------- */

    /** Define la carpeta base de almacenamiento de los logs */
    public static function setBasePath(string $path): void { self::$basePath = rtrim($path, '/'); }

    /** Define la cantidad de días que se conservarán los logs */
    public static function setRetention(int $days): void { self::$retentionDays = max(1, $days); }

    /** Activa o desactiva la salida en consola */
    public static function showInConsole(bool $show): void { self::$showInConsole = $show; }

    /** Define si los logs se guardarán en formato JSON */
    public static function setJsonFormat(bool $json): void { self::$jsonFormat = $json; }

    /** Activa el envío de logs críticos a un Webhook remoto */
    public static function enableWebhook(string $url): void { self::$webhookUrl = $url; }

    /** Activa o desactiva el modo buffer */
    public static function bufferMode(bool $enable): void { self::$bufferMode = $enable; }

    /**
     * Escribe en disco los logs acumulados en modo buffer y limpia el buffer.
     */
    public static function flushBuffer(): void
    {
        foreach (self::$buffer as $entry) self::store($entry);
        self::$buffer = [];
    }

    /** ---------------------- CAPTURA DE ERRORES ---------------------- */

    /**
     * Registra los manejadores de errores y excepciones para capturarlos automáticamente en los logs.
     * Captura errores estándar, excepciones no controladas y errores fatales.
     */
    public static function captureErrors(): void
    {
        set_error_handler(fn($errno, $errstr, $file, $line) =>
            self::write("PHP error: $errstr ($file:$line)", 'error')
        );

        set_exception_handler(fn($e) =>
            self::write("Uncaught exception: " . $e->getMessage(), 'critical', null, [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ])
        );

        register_shutdown_function(function() {
            $error = error_get_last();
            if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR])) {
                self::write("Fatal error: {$error['message']} ({$error['file']}:{$error['line']})", 'critical');
            }
        });
    }

    /** ---------------------- LECTURA ---------------------- */

    /**
     * Devuelve las últimas líneas registradas en el log del día actual.
     * Permite filtrar por categoría y limitar la cantidad de registros.
     */
    public static function getRecentLogs(?string $category = null, int $limit = 50): array
    {
        $folder = self::$basePath . ($category ? "/$category" : '');
        $file = "$folder/" . date('Y-m-d') . ".log";
        if (!file_exists($file)) return [];

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return array_slice(array_reverse($lines), 0, $limit);
    }

    /** ---------------------- ATAJOS ---------------------- */

    /** Métodos de conveniencia para registrar mensajes según su nivel */
    public static function debug(string $message, array $context = []): void { self::write($message, 'debug', null, $context); }
    public static function info(string $message, array $context = []): void { self::write($message, 'info', null, $context); }
    public static function success(string $message, array $context = []): void { self::write($message, 'success', null, $context); }
    public static function warning(string $message, array $context = []): void { self::write($message, 'warning', null, $context); }
    public static function error(string $message, array $context = []): void { self::write($message, 'error', null, $context); }
    public static function critical(string $message, array $context = []): void { self::write($message, 'critical', null, $context); }
}
