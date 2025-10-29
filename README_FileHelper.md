# SysKit FileHelper

**SysKit FileHelper** es una clase utilitaria avanzada para manejo de archivos y carpetas en PHP puro.  
Su propósito es ofrecer una solución ligera, robusta y multiplataforma (Windows / Linux) para desarrolladores que necesitan realizar operaciones comunes como compresión, copia, limpieza, y mantenimiento de archivos sin depender de frameworks.

---

## Características principales

- Compresión y descompresión en formato ZIP.
- Copia, movimiento y eliminación recursiva de archivos y directorios.
- Limpieza inteligente de archivos antiguos o innecesarios.
- Cálculo de tamaño de carpetas y conversión a formato legible.
- Operaciones seguras con validación de exclusiones y patrones.
- Métodos avanzados como sincronización (`mirror`), copia por flujo (`streamCopy`), verificación por hash (`verifyHash`), e inspección (`inspect`).

---

## Requisitos mínimos

- PHP 8.0 o superior  
- Extensión ZIP habilitada (`ext-zip`)

---

## Ejemplo rápido de uso

```php
use SysKit\Helpers\FileHelper;

// Crear ZIP
FileHelper::zip('/ruta/origen', '/ruta/backup.zip');

// Extraer ZIP
FileHelper::unzip('/ruta/backup.zip', '/ruta/destino');

// Copiar una carpeta completa
FileHelper::copy('/var/www/proyecto', '/var/www/backup');

// Eliminar archivos antiguos (más de 7 días)
FileHelper::deleteOldFiles('/var/logs', 7);

// Mostrar tamaño total de una carpeta
$size = FileHelper::humanSize(FileHelper::dirSize('/var/www'));
echo "Tamaño total: $size";
```

---

## Funciones principales

### Compresión y Descompresión
| Método | Descripción |
|---------|--------------|
| `zip($source, $destination, $exclude = [])` | Crea un archivo ZIP desde un archivo o carpeta completa. |
| `unzip($zipFile, $destination)` | Extrae el contenido de un ZIP en la ruta especificada. |

---

### Manipulación de archivos
| Método | Descripción |
|---------|--------------|
| `copy($source, $destination, $exclude = [])` | Copia archivos o carpetas completas (recursivo). |
| `move($source, $destination, $exclude = [])` | Mueve archivos o carpetas completas (recursivo). |
| `delete($path)` | Elimina archivo o carpeta recursivamente. |
| `ensureDirectory($dir)` | Crea un directorio si no existe. |
| `listFiles($path, $pattern = null)` | Lista archivos en una ruta (opcionalmente filtrados por patrón). |

---

### Limpieza y mantenimiento
| Método | Descripción |
|---------|--------------|
| `deleteOldFiles($path, $days, $options = [])` | Elimina archivos antiguos según días. Soporta exclusiones y modo simulación. |
| `keepRecentFiles($path, $keep = 5, $pattern = '*')` | Conserva solo los N archivos más recientes. |
| `archiveAndClean($source, $destination)` | Comprime una carpeta y elimina el original (backup automático). |

---

### Utilidades adicionales
| Método | Descripción |
|---------|--------------|
| `humanSize($bytes)` | Convierte bytes a formato legible (MB, GB, etc). |
| `dirSize($path)` | Calcula tamaño total de un directorio. |
| `streamCopy($source, $destination, $buffer = 8192)` | Copia archivos grandes en modo streaming (sin agotar memoria). |
| `verifyHash($file, $expectedHash, $algo = 'sha256')` | Verifica integridad de archivo mediante hash. |
| `mirror($source, $destination, $exclude = [])` | Sincroniza dos carpetas (replica y limpia las diferencias). |
| `inspect($path, $recursive = false)` | Devuelve información detallada de los archivos de un directorio. |

---

## Ejemplos prácticos

### Eliminar archivos antiguos con simulación
```php
$count = FileHelper::deleteOldFiles('/var/backups', 30, [
    'recursive' => true,
    'simulate' => true
]);
echo "Se eliminarían $count archivos.";
```

### Mantener solo los últimos 5 respaldos
```php
$removed = FileHelper::keepRecentFiles('/var/backups', 5, '*.zip');
echo "Se eliminaron $removed archivos antiguos.";
```

### Sincronizar carpetas (mirror)
```php
FileHelper::mirror('/var/www/proyecto', '/var/www/produccion');
```

### Validar integridad
```php
$isValid = FileHelper::verifyHash('/backup.zip', 'abc123...', 'sha256');
echo $isValid ? 'Archivo válido' : 'Archivo modificado o corrupto';
```

### Inspeccionar carpeta
```php
$info = FileHelper::inspect('/var/log', true);
print_r($info);
```

---

## Seguridad y compatibilidad

- Todas las operaciones son **seguras**: se validan rutas, permisos y extensiones.  
- Evita errores en entornos donde funciones del sistema no estén disponibles.  
- Compatible con **Windows, Linux y Docker**.

---

## Integración recomendada

SysKit FileHelper puede combinarse con otros módulos de la suite SysKit:
- `LogHelper`: para registrar acciones o errores de archivos.
- `SystemHelper`: para monitorear espacio en disco antes y después de operaciones.

---
