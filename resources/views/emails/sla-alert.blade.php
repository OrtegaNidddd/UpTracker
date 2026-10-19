<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerta de Nivel de Servicio - UpTracker</title>
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
            background-color: {{ $slaData['status'] === 'Incumple' ? '#dc2626' : '#d97706' }};
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
            background-color: {{ $slaData['status'] === 'Incumple' ? '#fee2e2' : '#fef3c7' }};
            color: {{ $slaData['status'] === 'Incumple' ? '#b91c1c' : '#b45309' }};
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
        .stat-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin: 16px 0;
        }
        .stat-card {
            background: #f1f5f9;
            padding: 12px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-number {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
        }
        .stat-title {
            font-size: 11px;
            text-transform: uppercase;
            color: #64748b;
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
            <h1>{{ $slaData['status'] === 'Incumple' ? '🚨 Incumplimiento de SLA Detectado' : '⚠️ Alerta Preventiva de Nivel de Servicio (SLA/SLO)' }}</h1>
        </div>
        <div class="content">
            <p>{{ $slaData['message'] }}</p>

            <div class="field-group">
                <div class="field-label">Servicio</div>
                <div class="field-value"><strong>{{ $service->name }}</strong> ({{ $service->url }})</div>
            </div>

            <div class="field-group">
                <div class="field-label">Clasificación SLA Actual</div>
                <div class="field-value">
                    <span class="badge">{{ $slaData['status'] }}</span>
                </div>
            </div>

            <div class="stat-grid">
                <div class="stat-card">
                    <div class="stat-title">SLI Disponibilidad Actual</div>
                    <div class="stat-number">{{ $slaData['uptime_sli'] }}%</div>
                    <div style="font-size: 10px; color: #64748b;">Objetivo (SLO): {{ $slaData['slo_availability_target'] }}%</div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">Presupuesto de Error Restante</div>
                    <div class="stat-number" style="color: {{ $slaData['error_budget_percentage'] < 20 ? '#dc2626' : '#d97706' }};">
                        {{ $slaData['error_budget_percentage'] }}%
                    </div>
                    <div style="font-size: 10px; color: #64748b;">Reserva de fallos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">Latencia Promedio</div>
                    <div class="stat-number">{{ $slaData['avg_latency_ms'] }} ms</div>
                    <div style="font-size: 10px; color: #64748b;">Objetivo: &le; {{ $slaData['slo_latency_target_ms'] }} ms</div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">Fecha / Hora</div>
                    <div class="stat-number" style="font-size: 14px; margin-top: 4px;">{{ now()->format('H:i:s d/m/Y') }}</div>
                </div>
            </div>

            <p style="font-size: 13px; color: #64748b; margin-top: 20px;">
                Se sugiere revisar la infraestructura, balanceadores o servicios dependientes antes de agotar por completo el presupuesto de indisponibilidad.
            </p>
        </div>
        <div class="footer">
            Mensaje preventivo generado automáticamente por el subsistema SLA/SLO de UpTracker.
        </div>
    </div>
</body>
</html>
