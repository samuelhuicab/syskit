# SysKit SystemHelper v2.0

**SysKit SystemHelper** es un módulo de monitoreo del sistema para PHP que ofrece información de rendimiento, entorno y recursos del servidor.  
Su objetivo es brindar a los desarrolladores métricas del sistema (CPU, RAM, disco, red, procesos, etc.) sin depender de extensiones externas ni herramientas de terceros.

---

## Requisitos

- PHP 8.0 o superior  
- Compatible con **Windows**, **Linux** y **Docker**.

---

## Ejemplo rápido de uso

```php
use SysKit\Helpers\SystemHelper;

// Información general del sistema
print_r(SystemHelper::getInfo());

// Métricas detalladas de rendimiento
print_r(SystemHelper::getMetrics());

// Exportar métricas como JSON
echo SystemHelper::export('json');

// Registrar el estado actual en logs (si se combina con LogHelper)
SystemHelper::logSystemStatus();
```

---

## Principales funcionalidades

| Método | Descripción |
|--------|--------------|
| `getInfo()` | Devuelve información general del entorno PHP y sistema operativo. |
| `getMetrics()` | Devuelve métricas detalladas del sistema (CPU, memoria, disco, etc.). |
| `getHealth()` | Evalúa el estado general del sistema ("OK", "HIGH LOAD", "CRITICAL"). |
| `logSystemStatus()` | Registra las métricas actuales en los logs de SysKit (opcional). |
| `export($format = 'json')` | Exporta métricas en formato JSON o HTML. |
| `getNetworkStats()` | Obtiene estadísticas básicas de red. |
| `getTemperature()` | Muestra la temperatura del CPU (si el sistema lo soporta). |

---

## Ejemplo detallado

### Obtener información del entorno
```php
$info = SystemHelper::getInfo();
print_r($info);

/*
Ejemplo de salida:
Array
(
    [php_version] => 8.2.1
    [os] => Linux
    [hostname] => server-01
    [ip] => 192.168.0.10
    [memory_usage] => 42.18 MB
    [disk_free] => 78.4 GB
    [uptime] => up 1 hour, 23 minutes
)
*/
```

---

### Obtener métricas completas
```php
$metrics = SystemHelper::getMetrics();
print_r($metrics);

/*
Ejemplo de salida:
Array
(
    [cpu_load] => Array ( [0] => 0.32 [1] => 0.28 [2] => 0.24 )
    [cpu_usage_percent] => 12.5
    [memory_usage] => 142.3 MB
    [memory_used_percent] => 55
    [disk_total] => 120 GB
    [disk_free] => 78.4 GB
    [php_processes] => 3
    [temperature] => 44.7 °C
    [time] => 2025-10-28 11:30:00
)
*/
```

---

### Exportar métricas como JSON o HTML
```php
echo SystemHelper::export('json');   // salida JSON
echo SystemHelper::export('html');   // salida HTML con formato
```

---

### Health Check rápido
```php
$status = SystemHelper::getHealth();
print_r($status);

/*
Array
(
    [status] => OK
    [timestamp] => 2025-10-28 11:35:12
    [details] => Array ( ... )
)
*/
```

## Integración práctica

SystemHelper está diseñado para integrarse con otros módulos de SysKit:
- **LogHelper** → Para registrar estados del sistema de forma automática.
- **FileHelper** → Para calcular espacio de almacenamiento y mantenimiento de logs.

---

## Uso en APIs y dashboards

Puedes usar `SystemHelper::export('json')` en un endpoint de tu API, por ejemplo `/api/system/status`, y obtener métricas en tiempo real para mostrarlas en un panel de control (Grafana, Vue, React, etc.).

Ejemplo de respuesta JSON:
```json
{
  "cpu_load": [0.32, 0.28, 0.24],
  "memory_used_percent": 55,
  "disk_free": "78.4 GB",
  "status": "OK",
  "time": "2025-10-28 11:35:12"
}
```

---

## Seguridad y compatibilidad

- No requiere permisos especiales ni funciones del sistema elevadas.  
- No usa `exec` en entornos Windows (manejo interno seguro).  
- No necesita dependencias externas (todo es PHP nativo).

---


