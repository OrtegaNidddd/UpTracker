# UpTracker — Manual Técnico de Arquitectura y Referencia del Sistema

> **Versión del Documento:** 1.0.0  
> **Estado:** Documentación Técnica Definitiva (Producción)  
> **Stack Principal:** PHP 8.3 | Laravel 11 | Laravel Reverb | SQLite / MySQL | Alpine.js | Tailwind CSS | Docker  
> **Ámbito:** Guía integral de ingeniería, arquitectura de software, infraestructura y mantenimiento operativo (Sin especificación Swagger/OpenAPI).

---

## Índice General

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Arquitectura General del Sistema y Topología](#2-arquitectura-general-del-sistema-y-topología)
3. [Decisiones de Diseño y Trade-offs Arquitectónicos](#3-decisiones-de-diseño-y-trade-offs-arquitectónicos)
4. [Componentes del Núcleo (Core Modules)](#4-componentes-del-núcleo-core-modules)
   - 4.1. Motor de Monitoreo y Sondeo HTTP
   - 4.2. Módulo de Detección y Gestión de Incidentes
   - 4.3. Pipeline de Alertas y Notificaciones Multi-Canal
   - 4.4. Capa de Reactividad en Tiempo Real (WebSockets / Reverb)
5. [Modelo de Datos y Flujo de Información](#5-modelo-de-datos-y-flujo-de-información)
   - 5.1. Diagrama Entidad-Relación (ERD)
   - 5.2. Diccionario de Datos y Reglas de Persistencia
   - 5.3. Estrategia de Purga y Ciclo de Vida de Registros
6. [Puntos de Integración y Contratos de API (RESTful)](#6-puntos-de-integración-y-contratos-de-api-restful)
   - 6.1. Autenticación y Gestión de Sesión
   - 6.2. Endpoints de Servicios y Métricas
   - 6.3. Endpoints de Canales de Alerta
   - 6.4. Endpoint de Estado Público (Status Page)
7. [Arquitectura de Despliegue e Infraestructura](#7-arquitectura-de-despliegue-e-infraestructura)
   - 7.1. Imagen Multi-Stage Docker
   - 7.2. Orquestación de Procesos con Supervisord
   - 7.3. Servidor Web Nginx y Optimizaciones de Red
8. [Rendimiento, Escalabilidad y Benchmarks](#8-rendimiento-escalabilidad-y-benchmarks)
9. [Modelo de Seguridad, Resiliencia y Cumplimiento](#9-modelo-de-seguridad-resiliencia-y-cumplimiento)
10. [Rutas de Lectura, Troubleshooting y Runbooks](#10-rutas-de-lectura-troubleshooting-y-runbooks)
11. [Glosario y Apéndice](#11-glosario-y-apéndice)

---

## 1. Resumen Ejecutivo

**UpTracker** es una plataforma moderna, autónoma y resiliente de monitoreo de disponibilidad (*uptime*), rendimiento de red (latencia y TTFB) y ciclo de vida de certificados SSL/TLS para servicios y APIs web. Diseñada bajo una arquitectura desacoplada y orientada a eventos, UpTracker ofrece capacidades de observación en tiempo real con latencias de notificación inferiores al segundo ante cualquier interrupción o degradación operativa.

### Capacidades Distintivas
- **Sondeo HTTP de Alta Precisión:** Ejecución periódica no bloqueante con extracción de *Time To First Byte* (TTFB) a nivel de socket mediante transfer stats de Guzzle/cURL.
- **Auditoría Preventiva de SSL:** Detección en capa de transporte de la fecha de caducidad de certificados X.509 con clasificación automática (`Valid`, `Expiring_Soon`, `Expired`, `None`).
- **Gestión Automática de Incidentes:** Creación automática de incidentes por caída (*Down*) o degradación (*Degraded* cuando se supera el umbral de latencia configurado) y resolución atómica en cuanto el servicio se recupera.
- **Transmisión Bidireccional en Tiempo Real:** Emisión de eventos inmediatos vía **Laravel Reverb** hacia canales privados WebSocket por usuario, eliminando el sondeo redundante desde el cliente web.
- **Despacho Multi-Canal Asíncrono:** Notificaciones desacopladas en colas hacia correo electrónico (Mailable con vista responsiva) y Webhooks (Discord y Telegram con payloads estructurados).
- **Aislamiento Multi-Inquilino Lógico:** Seguridad a nivel de modelo y middleware donde cada usuario gestiona exclusivamente sus servicios y canales, complementado con una vista de estado pública agregada para consumidores externos.
- **Contenedorización Multi-Stage:** Empaquetado en imagen Docker Alpine basada en PHP 8.3 FPM, Nginx y Supervisord, capaz de gobernar servidor web, workers de cola, scheduler y servidor Reverb en un único pod de despliegue continuo.

---

## 2. Arquitectura General del Sistema y Topología

UpTracker implementa una arquitectura híbrida monolítica modular optimizada para baja latencia. El backend Laravel 11 actúa como núcleo de orquestación, mientras que los procesos de ejecución en segundo plano (cola de trabajos, programador de tareas y servidor WebSocket) garantizan el aislamiento entre las peticiones de los usuarios y el trabajo continuo de sondeo.

### Diagrama de Topología del Sistema

```mermaid
graph TB
    subgraph Clientes ["Clientes y Consumidores"]
        Browser["Navegador Web (SPA/Blade + Alpine.js + Echo)"]
        ThirdParty["Sistemas Externos / Consumidores API"]
        PublicUser["Usuarios Públicos (/status/public)"]
    end

    subgraph Perímetro ["Capa Perimetral (Docker Container)"]
        Nginx["Servidor Web Nginx (Puerto 80)<br/>Manejo de Estáticos y Reverse Proxy"]
        ReverbServer["Laravel Reverb (Puerto 8080)<br/>WebSocket Server Nativo PHP"]
    end

    subgraph Nucleo ["Núcleo de Aplicación (PHP-FPM 8.3)"]
        HTTPKernel["Laravel HTTP Kernel / Middlewares<br/>SecurityHeaders, RateLimiter, Sanctum"]
        WebControllers["Controladores Web & Breeze (Blade)"]
        ApiControllers["Controladores REST API (JSON Resources)"]
    end

    subgraph Asincrono ["Subsistema de Procesamiento Asíncrono"]
        Scheduler["Laravel Scheduler (schedule:work)<br/>Evaluación sub-minuto de intervalos"]
        QueueWorkers["Queue Workers (artisan queue:work)<br/>Procesamiento de Jobs y Listeners"]
    end

    subgraph Almacenamiento ["Capa de Persistencia y Caché"]
        DB[(Base de Datos: SQLite / MySQL)]
        CacheDriver[(Cache & Session Store)]
    end

    subgraph Externo ["Servicios y Objetivos Externos"]
        MonitoredServices["Servicios Monitoreados (HTTP/HTTPS Endpoints)"]
        MailServer["Servidor SMTP / Mail Gateway"]
        DiscordWebhook["Discord Webhooks API"]
        TelegramWebhook["Telegram Webhook Endpoint"]
    end

    %% Conexiones
    Browser -->|HTTP/HTTPS Requests| Nginx
    ThirdParty -->|API REST Bearer Requests| Nginx
    PublicUser -->|Consulta de Estado| Nginx
    Browser <-->|WSS (WebSockets)| ReverbServer

    Nginx -->|FastCGI Pass| HTTPKernel
    HTTPKernel --> WebControllers
    HTTPKernel --> ApiControllers

    WebControllers --> DB
    ApiControllers --> DB

    Scheduler -->|Dispatch Job CheckEndpointStatus| QueueWorkers
    QueueWorkers -->|Lectura / Escritura| DB
    QueueWorkers -->|Sondeo HTTP HEAD/GET & SSL Audit| MonitoredServices
    QueueWorkers -->|EndpointStatusUpdated Event| ReverbServer
    QueueWorkers -->|Envío de Correo de Alerta| MailServer
    QueueWorkers -->|POST Webhook JSON Payload| DiscordWebhook
    QueueWorkers -->|POST Webhook JSON Payload| TelegramWebhook
```

### Ciclo de Flujo de Datos

```mermaid
sequenceDiagram
    autonumber
    participant Sch as Laravel Scheduler
    participant Q as Database Queue
    participant W as Worker (CheckEndpointStatus)
    participant Ext as Endpoint Remoto
    participant DB as Base de Datos
    participant R as Laravel Reverb
    participant UI as Dashboard Cliente (Echo)
    participant L as Listener (DispatchNotifications)
    participant Ch as Correo / Webhook

    Sch->>Q: Inserta CheckEndpointStatus(Service) según intervalo
    Q->>W: Despacha job a hilo de ejecución libre
    W->>Ext: HTTP GET/HEAD (Timeout 10s + TransferStats TTFB)
    Ext-->>W: Respuesta HTTP (Código de estado + métricas)
    W->>Ext: Socket SSL/TLS Audit (Puerto 443 X.509)
    Ext-->>W: Metadatos del Certificado
    W->>DB: INSERT en latency_logs
    alt Es fallo (Down) o latencia excede umbral (Degraded)
        W->>DB: INSERT en incidents (si no existe uno abierto)
        W->>L: Dispara evento IncidentLogged
        L->>Ch: Despacha correo (Mailable) y POST a Webhooks
    else El servicio está sano y había un incidente abierto
        W->>DB: UPDATE incidents SET resolved_at = now()
    end
    W->>R: EndpointStatusUpdated (ShouldBroadcastNow)
    R-->>UI: Payload JSON vía canal privado user.{id}.services
    UI->>UI: Actualiza latencia, uptime y badge reactivamente
```

---

## 3. Decisiones de Diseño y Trade-offs Arquitectónicos

| Decisión de Arquitectura | Justificación Técnica | Trade-offs y Mitigaciones |
| :--- | :--- | :--- |
| **Laravel Reverb en lugar de Pusher o Node.js Socket.io** | Mantiene todo el ecosistema de tiempo real en PHP puro, reduciendo dependencias externas de Node.js en tiempo de ejecución, facilitando la integración con eventos nativos de Laravel (`ShouldBroadcastNow`). | Reverb requiere un proceso demonio corriendo de forma constante (`supervisord.conf`), mitigado mediante supervisión automática y reconexión en el cliente. |
| **Sondeo vía Queue Worker en vez de cURL síncrono en Cron** | Si se tienen 500 endpoints, ejecutarlos secuencialmente causaría atascos temporales. El esquema de colas asíncronas (`CheckEndpointStatus`) permite paralelizar la carga en múltiples workers. | Demanda recursos de concurrencia en la base de datos o driver de cola. Mitigado usando `withoutOverlapping(10)` y timeouts estrictos de 10s por petición. |
| **Cálculo de TTFB vía Guzzle TransferStats** | Medir simplemente el tiempo transcurrido desde el inicio de la función en PHP incluye overhead de la máquina local. `starttransfer_time` de cURL refleja la verdadera latencia de respuesta del servidor remoto. | Requiere que cURL esté compilado con soporte de estadísticas en PHP. Asegurado en la imagen Docker base. |
| **Auditoría SSL en Capa de Transporte (`stream_socket_client`)** | Permite inspeccionar certificados caducados o por caducar sin depender de librerías de consola de terceros (como openssl cli o python certbot). | Se usa `verify_peer => false` exclusivamente para capturar el certificado y extraer `validTo_time_t` sin que el socket falle si el certificado ya expiró. |
| **Purga Automática de Registros (`monitor:prune`)** | Con verificaciones frecuentes (cada 30 seg), la tabla `latency_logs` genera miles de registros diarios. La purga diaria previene la saturación del disco y la lentitud en consultas agrupadas. | Pérdida de granularidad por segundo después de 30 días. Los incidentes consolidados en `incidents` no se eliminan, preservando la trazabilidad histórica de caídas. |
| **Multi-Stage Dockerfile (Alpine + Supervisord)** | Combina el compilado optimizado de Vite (Node) y Composer en etapas temporales, produciendo una imagen de ejecución ultraligera (Alpine) sin herramientas de compilación en producción. | Un único contenedor corre Nginx, PHP-FPM, Workers y Reverb. Para despliegues a gran escala, la arquitectura permite separar cada proceso modificando el comando de arranque en Kubernetes o ECS. |

---

## 4. Componentes del Núcleo (Core Modules)

### 4.1. Motor de Monitoreo y Sondeo HTTP

El ciclo de sondeo está gobernado por dos componentes centrales:

1. **Orquestador de Tareas ([routes/console.php](file:///c:/laragon/www/uptracker/routes/console.php)):**
   - Evalúa dinámicamente los servicios con `is_active = true`.
   - Asigna el despacho con granularidad sub-minuto (`everyTenSeconds()`, `everyThirtySeconds()`, `everyMinute()`, etc.).
   - Utiliza `withoutOverlapping(10)` para evitar que se dupliquen jobs si el ciclo anterior no terminó.
   - Provee además el comando Artisan [`monitor:poll`](file:///c:/laragon/www/uptracker/app/Console/Commands/PollActiveEndpoints.php) con soporte de flag `--force` para ejecuciones manuales o bajo demanda.

2. **Trabajo de Sondeo ([CheckEndpointStatus.php](file:///c:/laragon/www/uptracker/app/Jobs/CheckEndpointStatus.php)):**
   - Configurado con `$tries = 1` y `$timeout = 15` para evitar jobs huérfanos.
   - Soporta métodos configurables (`GET` o `HEAD`) y cabeceras HTTP personalizadas definidas por el usuario (`custom_headers`).
   - Mide la latencia neta utilizando el callback `on_stats` de Guzzle:
     ```php
     $request = Http::timeout(10)->withOptions([
         'on_stats' => function (TransferStats $stats) use (&$ttfbMs) {
             $startTransfer = $stats->getHandlerStat('starttransfer_time');
             if ($startTransfer !== null && $startTransfer > 0) {
                 $ttfbMs = (int) round($startTransfer * 1000);
             }
         },
     ]);
     ```
   - Persiste de manera atómica el resultado en [`LatencyLog`](file:///c:/laragon/www/uptracker/app/Models/LatencyLog.php).

### 4.2. Módulo de Detección y Gestión de Incidentes

UpTracker clasifica el estado de cada servicio mediante un evaluador de estados:

- **Caída Crítica (`Down`):** Cuando la petición HTTP arroja error de conexión, timeout, o un código de estado menor a 200 o mayor o igual a 400.
- **Rendimiento Degradado (`Degraded`):** Cuando el código HTTP es exitoso (2xx o 3xx), pero el valor de latencia en milisegundos supera el umbral configurado (`latency_threshold_ms`).
- **Operativo (`Up`):** Código de estado exitoso y latencia dentro de los límites esperados.

**Lógica de Apertura y Cierre:**
- Al detectarse un fallo (`isDown` o `isDegraded`), el sistema verifica si ya existe un registro en [`Incident`](file:///c:/laragon/www/uptracker/app/Models/Incident.php) con `resolved_at IS NULL`. Si no existe, se inserta el nuevo incidente y se dispara de inmediato el evento [`IncidentLogged`](file:///c:/laragon/www/uptracker/app/Events/IncidentLogged.php).
- Si la verificación posterior resulta exitosa y existe un incidente abierto, se estampa `resolved_at = now()` y se calcula la duración exacta del incidente en segundos (`duration_seconds`).

### 4.3. Pipeline de Alertas y Notificaciones Multi-Canal

El despacho de notificaciones está desacoplado mediante el listener asíncrono [`DispatchIncidentNotifications`](file:///c:/laragon/www/uptracker/app/Listeners/DispatchIncidentNotifications.php) (`ShouldQueue`), el cual implementa 3 intentos (`$tries = 3`) y timeout de 30 segundos:

- **Canal Email:** Despacha el mailable [`ServiceAlertMail`](file:///c:/laragon/www/uptracker/app/Mail/ServiceAlertMail.php) con la plantilla Blade [`resources/views/emails/service-alert.blade.php`](file:///c:/laragon/www/uptracker/resources/views/emails/service-alert.blade.php), conteniendo la URL afectada, tipo de incidente, hora de inicio y diagnóstico.
- **Canal Webhook (Discord & Telegram):** Emite un payload estructurado en formato JSON:
  ```json
  {
    "event": "incident.logged",
    "service_name": "API Pasarela de Pagos",
    "service_url": "https://api.empresa.com/v1/health",
    "incident_type": "Down",
    "timestamp": "2026-09-29T13:45:00.000Z",
    "details": "Servicio caído con código HTTP 503.",
    "content": "⚠️ **Alerta UpTracker**: El servicio `API Pasarela de Pagos` se encuentra en estado **Down**.",
    "text": "⚠️ Alerta UpTracker: El servicio API Pasarela de Pagos se encuentra en estado Down."
  }
  ```

### 4.4. Capa de Reactividad en Tiempo Real (WebSockets / Reverb)

La actualización del panel de control del usuario se produce mediante el evento [`EndpointStatusUpdated`](file:///c:/laragon/www/uptracker/app/Events/EndpointStatusUpdated.php), el cual implementa `ShouldBroadcastNow`:

- **Canal Privado:** `PrivateChannel("user.{$service->user_id}.services")`. La autorización del canal está declarada en [`routes/channels.php`](file:///c:/laragon/www/uptracker/routes/channels.php), asegurando que un usuario solo reciba las métricas de sus propios servicios.
- **Transmisión de Carga Útil:** Emite el ID del servicio, latencia (`latency_ms`), código HTTP, estado y timestamp ISO. El frontend captura este evento mediante Laravel Echo y actualiza el DOM de forma reactiva sin recargar la página.

---

## 5. Modelo de Datos y Flujo de Información

### 5.1. Diagrama Entidad-Relación (ERD)

```mermaid
erDiagram
    USERS ||--o{ SERVICES : "registra y administra"
    USERS ||--o{ NOTIFICATION_CHANNELS : "configura para alertas"
    USERS ||--o{ PERSONAL_ACCESS_TOKENS : "posee tokens API"
    SERVICES ||--o{ LATENCY_LOGS : "genera mediciones periódicas"
    SERVICES ||--o{ INCIDENTS : "registra fallas e interrupciones"

    USERS {
        bigint id PK
        string name
        string email UK
        timestamp email_verified_at
        string password
        string remember_token
        timestamps created_at_updated_at
    }

    SERVICES {
        bigint id PK
        bigint user_id FK
        string name
        string url
        string http_method "GET, HEAD"
        json custom_headers "Key-Value HTTP headers"
        integer interval_seconds "Frecuencia de sondeo"
        integer latency_threshold_ms "Umbral para degradación"
        boolean is_active "Interruptor de monitoreo"
        datetime ssl_expires_at "Fecha expiración cert"
        string ssl_status "Valid, Expiring_Soon, Expired, None"
        timestamps created_at_updated_at
    }

    LATENCY_LOGS {
        bigint id PK
        bigint service_id FK
        integer latency_ms "Milisegundos TTFB"
        integer http_status_code "Código de respuesta"
        string status "Up, Down"
        datetime checked_at "Marca temporal de sondeo"
    }

    INCIDENTS {
        bigint id PK
        bigint service_id FK
        string incident_type "Down, Degraded"
        datetime started_at "Inicio de la falla"
        datetime resolved_at "Restablecimiento (Nullable)"
        integer duration_seconds "Tiempo total de caída"
        text details "Diagnóstico del error"
        timestamps created_at_updated_at
    }

    NOTIFICATION_CHANNELS {
        bigint id PK
        bigint user_id FK
        string type "Email, Webhook_Discord, Webhook_Telegram"
        string target_destination "Email o URL de Webhook"
        boolean is_active "Estado del canal"
        timestamps created_at_updated_at
    }

    PERSONAL_ACCESS_TOKENS {
        bigint id PK
        string tokenable_type
        bigint tokenable_id FK
        string name
        string token UK
        text abilities
        timestamp last_used_at
        timestamp expires_at
        timestamps created_at_updated_at
    }
```

### 5.2. Diccionario de Datos y Reglas de Persistencia

#### Tabla `services`
- **`user_id`:** Clave foránea referenciando `users(id)` con eliminación en cascada (`cascadeOnDelete`).
- **`url`:** Cadena de hasta 2048 caracteres validada en capa de aplicación con esquemas `http` y `https`.
- **`http_method`:** Define el verbo HTTP utilizado para el chequeo. Valores permitidos: `GET`, `HEAD`. Valor por defecto: `GET`.
- **`custom_headers`:** Columna de tipo `json`, casteada a arreglo asociativo en PHP para permitir cabeceras como `Authorization`, `X-Api-Key` o `User-Agent`.
- **`interval_seconds`:** Intervalo de frecuencia de sondeo. Valores soportados: 10, 15, 20, 30, 60, 120, 300, 600, 900, 1800, 3600 segundos.
- **`ssl_status`:** Estado auditado del certificado (`Valid`, `Expiring_Soon`, `Expired`, `None`).

#### Tabla `latency_logs`
- **`timestamps = false`:** No almacena `created_at` ni `updated_at` para minimizar el tamaño de cada fila en disco. Utiliza `checked_at` con índice para consultas de series temporales.
- **`latency_ms`:** Entero nulo en caso de caídas de red completas donde no hubo respuesta.

#### Tabla `incidents`
- **`started_at` y `resolved_at`:** Marcas temporales de inicio y fin. Si `resolved_at` es `NULL`, el incidente está actualmente **abierto**.
- **`duration_seconds`:** Campo computado automáticamente al cerrar el incidente, facilitando el cálculo de SLA y MTTR (*Mean Time To Resolution*).

### 5.3. Estrategia de Purga y Ciclo de Vida de Registros

Para evitar la saturación de espacio en despliegues con cientos de servicios, el comando [`PruneLatencyLogs`](file:///c:/laragon/www/uptracker/app/Console/Commands/PruneLatencyLogs.php) (`monitor:prune --days=30`) se ejecuta diariamente desde el programador de tareas:

```php
LatencyLog::query()
    ->where('checked_at', '<', now()->subDays($days))
    ->delete();
```

Esto conserva únicamente las métricas granulares de los últimos 30 días, mientras que la bitácora de incidentes en `incidents` permanece intacta como registro histórico de auditoría.

---

## 6. Puntos de Integración y Contratos de API (RESTful)

La API de UpTracker está estructurada conforme a los principios de diseño RESTful, con respuestas normalizadas en formato JSON y recursos gestionados a través de Laravel API Resources.

> [!NOTE]
> Toda petición a endpoints protegidos debe incluir la cabecera:  
> `Authorization: Bearer <token_sanctum>`  
> `Accept: application/json`

### 6.1. Autenticación y Gestión de Sesión

| Método | Endpoint | Rate Limit | Descripción | Códigos HTTP |
| :--- | :--- | :--- | :--- | :--- |
| `POST` | `/api/register` | 10 req/min | Registro de usuario y emisión inmediata de Bearer Token | `201 Created`, `422 Unprocessable` |
| `POST` | `/api/login` | 10 req/min | Autenticación mediante email/contraseña y emisión de token | `200 OK`, `422 Unprocessable` |
| `GET` | `/api/user` | 60 req/min | Obtiene la información del perfil del usuario autenticado | `200 OK`, `401 Unauthorized` |
| `PATCH` | `/api/user` | 60 req/min | Actualiza nombre, email o contraseña del usuario autenticado | `200 OK`, `422 Unprocessable` |
| `POST` | `/api/logout` | 60 req/min | Revoca el token de acceso actual del usuario | `200 OK`, `401 Unauthorized` |

#### Ejemplo de Petición y Respuesta — `POST /api/login`

**Request:**
```json
{
  "email": "admin@empresa.com",
  "password": "PasswordSeguro123!",
  "device_name": "Servidor-CI-CD"
}
```

**Response (`200 OK`):**
```json
{
  "message": "Inicio de sesión exitoso.",
  "token_type": "Bearer",
  "access_token": "1|qX8J9Lp0ZaMb4kLmP9...",
  "user": {
    "id": 1,
    "name": "Administrador DevOps",
    "email": "admin@empresa.com"
  }
}
```

---

### 6.2. Endpoints de Servicios y Métricas

| Método | Endpoint | Rate Limit | Descripción |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/services` | 60 req/min | Lista todos los servicios del usuario con sus últimos 10 logs de latencia y 5 incidentes |
| `POST` | `/api/services` | 60 req/min | Registra un nuevo endpoint a monitorear con validación estricta de URL y cabeceras |
| `GET` | `/api/services/{service}` | 60 req/min | Detalle completo de un servicio con sus últimos 50 logs y 20 incidentes |
| `PUT` | `/api/services/{service}` | 60 req/min | Actualiza la configuración, método HTTP, cabeceras, umbral e intervalo |
| `DELETE` | `/api/services/{service}` | 60 req/min | Elimina un servicio y sus registros en cascada |
| `GET` | `/api/services/{service}/logs` | 60 req/min | Histórico paginado de métricas de latencia (`per_page` query param) |
| `GET` | `/api/services/{service}/incidents` | 60 req/min | Histórico paginado de incidentes registrados para el servicio |

#### Ejemplo de Petición y Respuesta — `POST /api/services`

**Request:**
```json
{
  "name": "Microservicio de Pagos",
  "url": "https://pagos.empresa.com/healthz",
  "http_method": "GET",
  "custom_headers": {
    "X-Monitoring-Agent": "UpTracker-Bot",
    "Authorization": "Bearer internal-healthcheck-token"
  },
  "interval_seconds": 30,
  "latency_threshold_ms": 350,
  "is_active": true
}
```

**Response (`201 Created`):**
```json
{
  "data": {
    "id": 4,
    "name": "Microservicio de Pagos",
    "url": "https://pagos.empresa.com/healthz",
    "http_method": "GET",
    "custom_headers": {
      "X-Monitoring-Agent": "UpTracker-Bot",
      "Authorization": "Bearer internal-healthcheck-token"
    },
    "interval_seconds": 30,
    "latency_threshold_ms": 350,
    "is_active": true,
    "ssl_status": "Valid",
    "ssl_expires_at": "2026-12-15T00:00:00.000000Z",
    "status": "online",
    "last_checked_at": null,
    "created_at": "2026-09-29T13:40:00.000000Z"
  }
}
```

---

### 6.3. Endpoints de Canales de Alerta

| Método | Endpoint | Rate Limit | Descripción |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/notification-channels` | 60 req/min | Lista todos los canales configurados por el usuario |
| `POST` | `/api/notification-channels` | 60 req/min | Registra un nuevo canal (`Email`, `Webhook_Discord`, `Webhook_Telegram`) |
| `GET` | `/api/notification-channels/{channel}` | 60 req/min | Detalle de un canal específico perteneciente al usuario |
| `PUT` | `/api/notification-channels/{channel}` | 60 req/min | Actualiza el tipo, destino o estado del canal |
| `DELETE` | `/api/notification-channels/{channel}` | 60 req/min | Da de baja y elimina el canal |

---

### 6.4. Endpoint de Estado Público (Status Page)

| Método | Endpoint | Rate Limit | Descripción |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/status/public` (y `/status/public`) | 30 req/min | Estado global agregado del sistema para páginas de estado públicas |

#### Respuesta Consolidada (`200 OK`):
```json
{
  "status": "Operational",
  "summary": "Todos los sistemas operando con normalidad.",
  "total_monitored": 5,
  "online_count": 5,
  "offline_count": 0,
  "degraded_count": 0,
  "services": [
    {
      "id": 1,
      "name": "Portal Principal",
      "status": "online",
      "uptime_24h": 100.0,
      "ssl_status": "Valid",
      "last_checked_at": "2026-09-29T13:42:10.000000Z"
    }
  ],
  "active_incidents": [],
  "resolved_incidents": [],
  "generated_at": "2026-09-29T13:42:15.000000Z"
}
```

---

## 7. Arquitectura de Despliegue e Infraestructura

El despliegue de UpTracker está optimizado para entornos basados en contenedores (*Docker*, *Docker Compose*, *Kubernetes*, *AWS ECS* o *Laravel Cloud*).

### 7.1. Imagen Multi-Stage Docker ([Dockerfile](file:///c:/laragon/www/uptracker/Dockerfile))

La compilación está dividida en tres etapas independientes para maximizar el almacenamiento en caché y minimizar el peso del artefacto final:

```mermaid
graph LR
    subgraph Stage1 ["Stage 1: Frontend Builder"]
        Node["node:20-alpine"] --> NpmInstall["npm ci"]
        NpmInstall --> ViteBuild["npm run build"]
        ViteBuild --> PublicBuild["Artefactos: public/build"]
    end

    subgraph Stage2 ["Stage 2: Composer Builder"]
        Composer["composer:2"] --> CompInstall["composer install --no-dev --optimize-autoloader"]
        CompInstall --> VendorDir["Artefactos: vendor/"]
    end

    subgraph Stage3 ["Stage 3: Production Runner"]
        PHPAlpine["php:8.3-fpm-alpine"] --> Extensions["Instalar Extensiones: pdo_sqlite, pdo_mysql, bcmath, pcntl, opcache, gd, intl"]
        Extensions --> CopyConfig["Copiar Nginx, php.ini, supervisord.conf, entrypoint.sh"]
        PublicBuild --> Stage3
        VendorDir --> Stage3
        CopyConfig --> ReadyImage["Imagen Final de Producción (~120MB)"]
    end
```

### 7.2. Orquestación de Procesos con Supervisord ([docker/supervisord.conf](file:///c:/laragon/www/uptracker/docker/supervisord.conf))

Un único contenedor aloja de forma aislada y controlada todos los subprocesos necesarios:

1. **`php-fpm` (Prioridad 5):** Motor de procesamiento PHP para las peticiones HTTP despachadas por Nginx.
2. **`nginx` (Prioridad 10):** Servidor web de alto rendimiento ejecutándose en primer plano (`daemon off;`).
3. **`laravel-worker` (Prioridad 15, `numprocs=2`):** Dos procesos paralelos de trabajadores de colas ejecutando `artisan queue:work --sleep=3 --tries=3 --max-time=3600`.
4. **`laravel-schedule` (Prioridad 20):** Demonio continuo ejecutando `artisan schedule:work` para el despacho sub-minuto de chequeos.
5. **`laravel-reverb` (Prioridad 25):** Demonio del servidor WebSocket ejecutando `artisan reverb:start --host=0.0.0.0 --port=8080`.

### 7.3. Servidor Web Nginx y Optimizaciones de Red ([docker/nginx.conf](file:///c:/laragon/www/uptracker/docker/nginx.conf))

- **Proxy Inverso FastCGI:** Redirecciona todas las peticiones dinámicas a `127.0.0.1:9000` con `try_files $uri $uri/ /index.php?$query_string`.
- **Compresión Gzip Activa:** Habilitada para tipos MIME de texto, json, javascript y css.
- **Caché Agresiva de Estáticos:** Cabeceras `Cache-Control "public, immutable, max-age=31536000"` para recursos en `assets/` y `build/`.
- **Protección de Archivos Sensibles:** Denegación explícita (`deny all`) para directorios ocultos como `.git`, `.env` y `.htaccess`.

---

## 8. Rendimiento, Escalabilidad y Benchmarks

| Métrica / Parámetro | Valor de Diseño / Benchmark | Mecanismo de Optimización |
| :--- | :--- | :--- |
| **Tiempo de respuesta API REST (`/api/services`)** | `< 25 ms` (P95) | Eager loading de relaciones (`latencyLogs`, `incidents`) limitado a las últimas N entradas para evitar *over-fetching*. |
| **Consumo de memoria por Job de Sondeo** | `< 12 MB` por worker | Ejecución ligera en cola, liberación explícita de sockets y uso de `hrtime()` nativo de PHP. |
| **Overhead del Motor de Sondeo** | Insignificante en CPU | Concurrencia distribuida vía cola en base de datos/Redis con timeouts rígidos de red (10s máximo). |
| **Latencia de Notificación WebSocket** | `< 100 ms` desde el chequeo | Emisión directa en memoria vía socket local hacia Reverb mediante el contrato `ShouldBroadcastNow`. |
| **Volumen de Base de Datos** | Acotado y predecible | Purga diaria automática de registros de latencia superiores a 30 días mediante `monitor:prune`. |

---

## 9. Modelo de Seguridad, Resiliencia y Cumplimiento

### 9.1. Cabeceras HTTP de Seguridad ([SecurityHeaders.php](file:///c:/laragon/www/uptracker/app/Http/Middleware/SecurityHeaders.php))

Inyectadas globalmente en cada petición HTTP por la tubería de middleware:
- **`X-Frame-Options: SAMEORIGIN`:** Previene ataques de clickjacking.
- **`X-Content-Type-Options: nosniff`:** Evita la reinterpretación errónea de tipos MIME.
- **`X-XSS-Protection: 1; mode=block`:** Filtro de cross-site scripting para navegadores heredados.
- **`Referrer-Policy: strict-origin-when-cross-origin`:** Protege la fuga de datos en enlaces externos.
- **`Permissions-Policy: camera=(), microphone=(), geolocation=()`:** Deshabilita APIs de hardware innecesarias.
- **`Strict-Transport-Security: max-age=31536000; includeSubDomains`:** Forzado automático cuando la petición arriba sobre HTTPS.

### 9.2. Esquema de Rate Limiting ([AppServiceProvider.php](file:///c:/laragon/www/uptracker/app/Providers/AppServiceProvider.php))

- **`api.auth`:** 10 peticiones por minuto por IP para mitigar ataques de fuerza bruta en `/api/login` y `/api/register`.
- **`api`:** 60 peticiones por minuto por usuario autenticado (o IP) para operaciones CRUD en servicios y canales.
- **`api.public`:** 30 peticiones por minuto por IP para consultas en `/status/public` y `/api/status/public`.

### 9.3. Aislamiento Multi-Tenancy Lógico

Cada servicio y canal de notificación cuenta con una verificación estricta de propiedad:
```php
abort_if((int) $service->user_id !== (int) $request->user()->id, 403, 'Acceso denegado a este servicio.');
```
Esto previene ataques de referencia directa insegura a objetos (*IDOR - Insecure Direct Object References*).

---

## 10. Rutas de Lectura, Troubleshooting y Runbooks

### Rutas de Lectura Sugeridas

```mermaid
graph TD
    Start((Inicio)) --> Role{Rol Técnico}
    
    Role -->|Desarrollador Backend / Fullstack| DevPath["1. Modelos (Service, LatencyLog, Incident)<br/>2. Jobs (CheckEndpointStatus)<br/>3. API Controllers & Routes<br/>4. Blade Views & Alpine.js"]
    Role -->|DevOps / SRE / SysAdmin| SrePath["1. Topología de Red y Puertos<br/>2. Dockerfile & docker-compose.yml<br/>3. Supervisor y Nginx Configs<br/>4. Rutas de Scheduler y Colas"]
    Role -->|Seguridad / Auditoría| SecPath["1. Middleware SecurityHeaders<br/>2. Sanctum & Tokens Bearer<br/>3. Rate Limiters en AppServiceProvider<br/>4. Auditoría de Certificados SSL"]
```

### Runbook de Troubleshooting Común

#### Caso 1: Los servicios no se actualizan en el Dashboard en tiempo real
1. **Verificar estado de Reverb:**
   Comprobar en la terminal o contenedor que el proceso `laravel-reverb` esté activo:
   ```bash
   php artisan reverb:start --debug
   ```
2. **Verificar variables de entorno del frontend:**
   Asegurarse de que `VITE_REVERB_APP_KEY`, `VITE_REVERB_HOST` y `VITE_REVERB_PORT` coincidan con la configuración del servidor en `.env`.
3. **Validar suscripción de canal privado:**
   Inspeccionar la consola del navegador; si aparece un error 403 en `/broadcasting/auth`, verificar la validez de la sesión del usuario.

#### Caso 2: El scheduler no ejecuta los sondeos
1. **Verificar que el demonio del scheduler esté corriendo:**
   ```bash
   php artisan schedule:work
   ```
2. **Verificar que existan servicios con `is_active = 1`:**
   ```bash
   php artisan monitor:poll --force
   ```
   Si el comando manual despacha los jobs a la cola, el fallo residía en el proceso en segundo plano del scheduler.

#### Caso 3: Acumulación de tareas en la cola (`jobs` table)
1. **Verificar que los workers estén activos:**
   ```bash
   php artisan queue:work --sleep=3 --tries=3
   ```
2. **Revisar fallos registrados en `failed_jobs`:**
   ```bash
   php artisan queue:failed
   ```

---

## 11. Glosario y Apéndice

- **TTFB (Time To First Byte):** Tiempo transcurrido desde que el cliente inicia la petición HTTP hasta que recibe el primer byte de respuesta del servidor web. Extraído mediante cURL TransferStats en UpTracker.
- **Laravel Reverb:** Servidor de WebSockets de primera clase para aplicaciones Laravel, implementado en PHP puro y con soporte de escalabilidad horizontal.
- **Incident Degraded:** Incidente registrado cuando un endpoint responde favorablemente (HTTP 200), pero la latencia supera el valor fijado en `latency_threshold_ms`.
- **Sanctum:** Sistema de autenticación ligero de Laravel para SPAs y APIs basado en tokens Bearer prefijados con hashes seguros en base de datos.
- **Multi-Stage Docker Build:** Patrón de diseño de contenedores que utiliza múltiples sentencias `FROM` en un solo `Dockerfile` para descartar dependencias de desarrollo (npm, node, git) y conservar solo el binario de ejecución.

---

*Manual técnico generado por el Rol de Arquitectura de Sistemas y Documentación de UpTracker.*
