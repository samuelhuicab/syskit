# SysKit LogHelper v2.1

**SysKit LogHelper** es una librería ligera de PHP para gestión avanzada de logs, diseñada para proyectos que necesitan trazabilidad, control de errores y monitoreo sin depender de frameworks pesados.

Su propósito es ofrecer una herramienta profesional, escalable y fácil de integrar en cualquier tipo de proyecto PHP (tanto independiente como dentro de frameworks).

---

## Índice

1. [Características](#características)
2. [Requisitos](#requisitos)
3. [Instalación](#instalación)
4. [Uso básico](#uso-básico)
5. [Configuración avanzada](#configuración-avanzada)
6. [Captura automática de errores](#captura-automática-de-errores)
7. [Modo buffer](#modo-buffer)
8. [Lectura de logs recientes](#lectura-de-logs-recientes)
9. [Integraciones prácticas](#integraciones-prácticas)
   - [Integración con Laravel](#integración-con-laravel)
   - [Integración en proyectos PHP tradicionales](#integración-en-proyectos-php-tradicionales)
   - [Integración con cron jobs o tareas automáticas](#integración-con-cron-jobs-o-tareas-automáticas)
10. [Buenas prácticas](#buenas-prácticas)
11. [Licencia](#licencia)
12. [Autor](#autor)

---

## Características

- Sistema de logs con niveles estándar: `debug`, `info`, `success`, `warning`, `error`, `critical`.
- Soporte de formato **texto** o **JSON**.
- **Rotación automática** de archivos antiguos según los días configurados.
- **Captura automática** de errores, excepciones y errores fatales de PHP.
- **Envío de alertas HTTP** a un Webhook en caso de errores o eventos críticos.
- **Modo buffer** para optimizar rendimiento en procesos con muchos registros.
- **Salida en consola** (CLI) con colores por nivel.
- Compatible con PHP 7.4 y superiores.

---

## Requisitos

- PHP 7.4 o superior.
- Permisos de escritura en el directorio donde se almacenarán los logs.
- Extensiones nativas: `json`, `date`, `fileinfo`.

---

## Instalación

### Desde Composer (recomendado)

```bash
composer require samuelhuicab/syskit
```

### Manualmente

Clona el repositorio y ejecuta:

```bash
composer install
```

Luego incluye el autoload:

```php
require __DIR__ . '/vendor/autoload.php';
```

---

## Uso básico

### Registrar eventos simples

```php
use SysKit\Helpers\LogHelper;

LogHelper::info('Inicio del sistema');
LogHelper::success('Usuario autenticado correctamente');
LogHelper::error('Error al conectar con la base de datos');
```

### Registrar eventos con contexto

```php
LogHelper::warning('El usuario {user} intentó acceder sin permisos', [
    'user' => 'syskit',
    'ip' => '192.168.1.45'
]);
```

Salida en archivo:
```
[2025-10-28 12:30:11] [warning] El usuario sam intentó acceder sin permisos {"user":"sam","ip":"192.168.1.45"}
```

---

## Configuración avanzada

### Cambiar la carpeta de logs
```php
LogHelper::setBasePath('/var/www/mislogs');
```

### Cambiar los días de retención
```php
LogHelper::setRetention(10); // Elimina los logs con más de 10 días
```

### Desactivar salida en consola
```php
LogHelper::showInConsole(false);
```

### Guardar en formato JSON
```php
LogHelper::setJsonFormat(true);
```

Ejemplo de salida:
```json
{
  "time": "2025-10-28 12:45:00",
  "level": "info",
  "message": "Servicio iniciado",
  "context": {}
}
```

### Habilitar alertas por Webhook
```php
LogHelper::enableWebhook('https://mi-servidor.com/alertas');
```

Cualquier `error` o `critical` enviará automáticamente un `POST` con el contenido del log.

---

## Captura automática de errores

LogHelper puede registrar automáticamente errores y excepciones sin necesidad de `try/catch` manuales.

```php
LogHelper::captureErrors();

// Ejemplo: forzar un error
trigger_error("Archivo no encontrado", E_USER_WARNING);
```

Registra automáticamente:
- Errores (`E_WARNING`, `E_NOTICE`, etc.)
- Excepciones no capturadas (`throw new Exception`)
- Errores fatales (`E_ERROR`, `E_CORE_ERROR`, etc.)

---

## Modo buffer

Permite acumular múltiples logs en memoria y escribirlos en una sola operación, mejorando el rendimiento en procesos masivos.

```php
LogHelper::bufferMode(true);

LogHelper::info('Inicio de tarea');
LogHelper::info('Procesando lote A');
LogHelper::info('Procesando lote B');

LogHelper::flushBuffer(); // Guarda todos los logs en disco
```

---

## Lectura de logs recientes

Para obtener los últimos registros del día actual:

```php
$logs = LogHelper::getRecentLogs(null, 5);
print_r($logs);
```

---

## Integraciones prácticas

### Integración con Laravel

SysKit LogHelper puede convivir con el sistema de logging nativo de Laravel para registrar eventos adicionales o crear un canal paralelo de logs.

**Ejemplo de integración:**

```php
namespace App\Services;

use SysKit\Helpers\LogHelper;

class ExternalLogger
{
    public function registrarEvento($mensaje, $nivel = 'info')
    {
        LogHelper::write($mensaje, $nivel);
    }
}
```

Y desde cualquier controlador:
```php
$logger = new \App\Services\ExternalLogger();
$logger->registrarEvento('Inicio de proceso externo', 'info');
```

**Integración avanzada con Laravel Exceptions Handler:**

Puedes modificar `app/Exceptions/Handler.php`:
```php
use SysKit\Helpers\LogHelper;

public function report(Throwable $exception)
{
    LogHelper::critical('Excepción no capturada', [
        'error' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine()
    ]);

    parent::report($exception);
}
```

Esto garantiza que todos los errores de Laravel también sean registrados por SysKit.

---

### Integración en proyectos PHP tradicionales

Ideal para proyectos sin frameworks o sistemas personalizados:

```php
require __DIR__ . '/vendor/autoload.php';
use SysKit\Helpers\LogHelper;

LogHelper::captureErrors();

try {
    $conexion = new PDO('mysql:host=localhost;dbname=test', 'root', '');
    LogHelper::success('Conexión establecida correctamente');
} catch (Exception $e) {
    LogHelper::error('Error de conexión: ' . $e->getMessage());
}
```

---

### Integración con cron jobs o tareas automáticas

SysKit LogHelper puede utilizarse en scripts ejecutados periódicamente (cron) para registrar la ejecución de tareas y detectar fallos.

```bash
# Ejecutar cada 5 minutos
*/5 * * * * php /var/www/html/scripts/tarea.php >> /var/www/html/logs/cron.log 2>&1
```

Y en el script PHP:

```php
use SysKit\Helpers\LogHelper;

require __DIR__ . '/../vendor/autoload.php';

LogHelper::info('Tarea cron iniciada');
LogHelper::debug('Sincronizando base de datos');
LogHelper::success('Sincronización completada correctamente');
```

En caso de error, también puedes usar Webhooks para enviar alertas automáticas.


