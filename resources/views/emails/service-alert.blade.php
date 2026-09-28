<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerta de Incidente - UpTracker</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            margin: 0;
            padding: 24px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .header {
            background-color: {{ $incident->incident_type === 'Down' ? '#dc2626' : '#d97706' }};
            color: #ffffff;
            padding: 24px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
        }
        .content {
            padding: 24px;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            background-color: {{ $incident->incident_type === 'Down' ? '#fee2e2' : '#fef3c7' }};
            color: {{ $incident->incident_type === 'Down' ? '#b91c1c' : '#b45309' }};
        }
        .field-group {
            margin-bottom: 16px;
        }
        .field-label {
            font-size: 12px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .field-value {
            font-size: 15px;
            color: #0f172a;
            word-break: break-all;
        }
        .footer {
            background-color: #f1f5f9;
            padding: 16px;
            text-align: center;
            font-size: 12px;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Alerta de Monitoreo - UpTracker</h1>
        </div>
        <div class="content">
            <p>Se ha detectado un incidente que requiere tu atención:</p>

            <div class="field-group">
                <div class="field-label">Servicio</div>
                <div class="field-value"><strong>{{ $service->name }}</strong></div>
            </div>

            <div class="field-group">
                <div class="field-label">Estado del Incidente</div>
                <div class="field-value">
                    <span class="badge">{{ $incident->incident_type }}</span>
                </div>
            </div>

            <div class="field-group">
                <div class="field-label">URL / Endpoint Afectado</div>
                <div class="field-value">
                    <a href="{{ $service->url }}" style="color: #2563eb; text-decoration: none;">{{ $service->url }}</a>
                </div>
            </div>

            <div class="field-group">
                <div class="field-label">Fecha y Hora de Inicio</div>
                <div class="field-value">
                    {{ $incident->started_at ? $incident->started_at->format('Y-m-d H:i:s T') : now()->format('Y-m-d H:i:s T') }}
                </div>
            </div>

            @if($incident->details)
            <div class="field-group">
                <div class="field-label">Detalles del Fallo</div>
                <div class="field-value" style="background: #f8fafc; padding: 10px; border-radius: 6px; font-family: monospace; font-size: 13px;">
                    {{ $incident->details }}
                </div>
            </div>
            @endif
        </div>
        <div class="footer">
            Mensaje generado automáticamente por el sistema de monitoreo UpTracker.
        </div>
    </div>
</body>
</html>
