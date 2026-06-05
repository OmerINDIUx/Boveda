@extends('layouts.app')

@section('title', 'Dashboard Ejecutivo')

@section('content')
@php
    $stageLabels = [
        'planeacion' => 'Planeación',
        'diseno' => 'Diseño',
        'construccion' => 'Construcción',
        'cierre' => 'Cierre',
        'pausado' => 'Pausado',
    ];
    $priorityLabels = [
        'baja' => 'Baja',
        'media' => 'Media',
        'alta' => 'Alta',
        'critica' => 'Crítica',
    ];
    $renewalFrequencyLabels = [
        'once' => 'Única',
        'weekly' => 'Semanal',
        'monthly' => 'Mensual',
        'yearly' => 'Anual',
    ];
    $maxDisciplineCount = max(1, $disciplineBreakdown->max('count') ?? 1);
    $maxTrend = max(1, max($revisionTrendData ?: [1]));
    $totalStatus = max(1, array_sum($statusBuckets));
@endphp

<style>
    .dashboard-shell {
        --dashboard-soft: #f8fafc;
        --dashboard-track: #e2e8f0;
        --dashboard-avatar-bg: #e0f2fe;
        --dashboard-avatar-text: #0369a1;
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }

    body.dark-mode .dashboard-shell {
        --dashboard-soft: rgba(15, 23, 42, 0.55);
        --dashboard-track: rgba(148, 163, 184, 0.22);
        --dashboard-avatar-bg: rgba(14, 165, 233, 0.16);
        --dashboard-avatar-text: #7dd3fc;
    }

    .dashboard-hero {
        display: grid;
        grid-template-columns: minmax(0, 1.6fr) minmax(320px, 0.9fr);
        gap: 1rem;
        align-items: stretch;
    }

    .hero-panel {
        background: #0f172a;
        color: white;
        border-radius: 8px;
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 210px;
        box-shadow: var(--shadow);
    }

    .hero-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 1rem;
    }

    .hero-chip {
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 999px;
        padding: 0.45rem 0.7rem;
        color: #dbeafe;
        font-size: 0.72rem;
        font-weight: 800;
    }

    .dashboard-title {
        font-size: 2rem;
        line-height: 1.05;
        letter-spacing: 0;
        margin: 0.35rem 0 0;
        max-width: 780px;
    }

    .dashboard-subtitle {
        color: #cbd5e1;
        font-size: 0.9rem;
        font-weight: 600;
        max-width: 740px;
        margin-top: 0.75rem;
    }

    .dashboard-actions {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .button-flat {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.45rem;
        min-height: 40px;
        padding: 0.7rem 0.95rem;
        border-radius: 8px;
        border: 1px solid var(--border);
        background: var(--bg-card);
        color: var(--text-main);
        text-decoration: none;
        font-weight: 800;
        font-size: 0.78rem;
        white-space: nowrap;
    }

    .button-flat.primary {
        background: var(--primary);
        border-color: var(--primary);
        color: white;
    }

    .health-panel {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 1.2rem;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 210px;
        box-shadow: var(--shadow);
    }

    .renewal-signal {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 0.85rem;
    }

    .renewal-status-card {
        border-radius: 8px;
        padding: 0.9rem;
        border: 1px solid var(--border);
        background: var(--dashboard-soft);
        position: relative;
        overflow: hidden;
    }

    .renewal-status-card::before {
        content: "";
        position: absolute;
        inset: 0 auto 0 0;
        width: 6px;
        background: #10b981;
    }

    .renewal-status-card.status-red::before { background: #ef4444; }
    .renewal-status-card.status-yellow::before { background: #f59e0b; }
    .renewal-status-card.status-green::before { background: #10b981; }

    .renewal-status-card.status-red { background: rgba(254, 226, 226, 0.45); }
    .renewal-status-card.status-yellow { background: rgba(254, 243, 199, 0.48); }
    .renewal-status-card.status-green { background: rgba(209, 250, 229, 0.48); }

    body.dark-mode .renewal-status-card.status-red { background: rgba(127, 29, 29, 0.22); }
    body.dark-mode .renewal-status-card.status-yellow { background: rgba(120, 53, 15, 0.22); }
    body.dark-mode .renewal-status-card.status-green { background: rgba(6, 78, 59, 0.24); }

    .renewal-status-row {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
    }

    .renewal-score {
        display: flex;
        align-items: baseline;
        gap: 0.45rem;
    }

    .renewal-score strong {
        font-size: 2.15rem;
        line-height: 1;
        font-weight: 900;
        color: var(--text-main);
    }

    .renewal-score span {
        color: var(--text-muted);
        font-size: 0.78rem;
        font-weight: 900;
    }

    .semaphore-bar {
        display: flex;
        height: 12px;
        overflow: hidden;
        border-radius: 999px;
        background: var(--dashboard-track);
        margin-top: 0.85rem;
    }

    .semaphore-bar span:nth-child(1) { background: #10b981; }
    .semaphore-bar span:nth-child(2) { background: #f59e0b; }
    .semaphore-bar span:nth-child(3) { background: #ef4444; }
    .semaphore-bar span:nth-child(4) { background: #94a3b8; }

    .semaphore-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.55rem;
        margin-top: 0.85rem;
    }

    .semaphore-count {
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 0.55rem;
        background: var(--dashboard-soft);
    }

    .semaphore-count strong {
        display: block;
        font-size: 1.1rem;
        color: var(--text-main);
        font-weight: 900;
    }

    .semaphore-count span {
        display: block;
        margin-top: 0.18rem;
        color: var(--text-muted);
        font-size: 0.62rem;
        font-weight: 900;
        text-transform: uppercase;
    }

    .semaphore-count.green { border-left: 4px solid #10b981; }
    .semaphore-count.yellow { border-left: 4px solid #f59e0b; }
    .semaphore-count.red { border-left: 4px solid #ef4444; }
    .semaphore-count.gray { border-left: 4px solid #94a3b8; }

    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1rem;
    }

    .kpi-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 1rem;
        min-height: 132px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        box-shadow: var(--shadow);
    }

    .kpi-icon {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        display: grid;
        place-items: center;
        color: white;
    }

    .kpi-topline {
        display: flex;
        justify-content: space-between;
        gap: 0.75rem;
        align-items: flex-start;
    }

    .kpi-label {
        font-size: 0.7rem;
        color: var(--text-muted);
        font-weight: 900;
        text-transform: uppercase;
    }

    .kpi-value {
        font-size: 2rem;
        line-height: 1;
        font-weight: 900;
        color: var(--text-main);
        margin-top: 0.75rem;
    }

    .kpi-note {
        color: var(--text-muted);
        font-size: 0.72rem;
        font-weight: 700;
        margin-top: 0.55rem;
    }

    .dashboard-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.35fr) minmax(340px, 0.85fr);
        gap: 1rem;
    }

    .panel {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 1rem;
        box-shadow: var(--shadow);
    }

    .panel-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .panel-title {
        font-size: 0.95rem;
        font-weight: 900;
        color: var(--text-main);
        margin: 0;
    }

    .panel-kicker {
        color: var(--text-muted);
        font-size: 0.7rem;
        font-weight: 800;
        margin-top: 0.25rem;
    }

    .status-strip {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        overflow: hidden;
        border-radius: 8px;
        border: 1px solid var(--border);
    }

    .status-block {
        padding: 0.9rem;
        border-right: 1px solid var(--border);
        min-height: 92px;
    }

    .status-block:last-child {
        border-right: none;
    }

    .status-number {
        font-size: 1.55rem;
        font-weight: 900;
        margin-top: 0.35rem;
    }

    .bar-list {
        display: flex;
        flex-direction: column;
        gap: 0.8rem;
    }

    .bar-row {
        display: grid;
        grid-template-columns: 120px minmax(0, 1fr) 44px;
        gap: 0.75rem;
        align-items: center;
        font-size: 0.76rem;
        font-weight: 800;
    }

    .bar-track {
        height: 9px;
        background: var(--dashboard-track);
        border-radius: 999px;
        overflow: hidden;
    }

    .bar-fill {
        height: 100%;
        background: #2563eb;
        border-radius: 999px;
    }

    .trend-bars {
        height: 190px;
        display: grid;
        grid-template-columns: repeat(8, minmax(0, 1fr));
        align-items: end;
        gap: 0.55rem;
        padding-top: 1rem;
    }

    .trend-column {
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 0.45rem;
        align-items: center;
        justify-content: flex-end;
        height: 100%;
    }

    .trend-bar {
        width: 100%;
        min-height: 4px;
        border-radius: 6px 6px 0 0;
        background: #14b8a6;
    }

    .trend-label {
        color: var(--text-muted);
        font-size: 0.62rem;
        font-weight: 800;
        text-align: center;
    }

    .queue-list {
        display: flex;
        flex-direction: column;
        gap: 0.65rem;
    }

    .queue-item {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 0.8rem;
        padding: 0.75rem;
        border-radius: 8px;
        border: 1px solid var(--border);
        background: var(--dashboard-soft);
    }

    .queue-title {
        font-weight: 900;
        color: var(--text-main);
        font-size: 0.78rem;
        overflow-wrap: anywhere;
    }

    .queue-meta {
        color: var(--text-muted);
        font-size: 0.68rem;
        font-weight: 700;
        margin-top: 0.22rem;
    }

    .pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        padding: 0.35rem 0.55rem;
        font-size: 0.64rem;
        font-weight: 900;
        white-space: nowrap;
    }

    .pill.danger { background: #fee2e2; color: #991b1b; }
    .pill.warning { background: #fef3c7; color: #92400e; }
    .pill.good { background: #d1fae5; color: #065f46; }
    .pill.neutral { background: #e2e8f0; color: #334155; }

    .alert-stack {
        display: flex;
        flex-direction: column;
        gap: 0.65rem;
    }

    .alert-row {
        border-radius: 8px;
        padding: 0.8rem;
        border: 1px solid var(--border);
        background: var(--dashboard-soft);
    }

    .alert-row.danger { border-color: #fecaca; background: rgba(254, 226, 226, 0.55); }
    .alert-row.warning { border-color: #fde68a; background: rgba(254, 243, 199, 0.55); }

    body.dark-mode .alert-row.danger { border-color: rgba(248, 113, 113, 0.32); background: rgba(127, 29, 29, 0.26); }
    body.dark-mode .alert-row.warning { border-color: rgba(251, 191, 36, 0.32); background: rgba(120, 53, 15, 0.24); }

    .alert-title {
        font-size: 0.78rem;
        font-weight: 900;
        color: var(--text-main);
    }

    .alert-detail {
        margin-top: 0.25rem;
        font-size: 0.7rem;
        color: var(--text-muted);
        font-weight: 700;
    }

    .activity-row {
        display: grid;
        grid-template-columns: 34px 1fr;
        gap: 0.7rem;
        padding-bottom: 0.75rem;
        margin-bottom: 0.75rem;
        border-bottom: 1px solid var(--border);
    }

    .activity-row:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }

    .avatar {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        background: var(--dashboard-avatar-bg);
        color: var(--dashboard-avatar-text);
        display: grid;
        place-items: center;
        font-size: 0.68rem;
        font-weight: 900;
    }

    @media (max-width: 1180px) {
        .dashboard-hero,
        .dashboard-grid {
            grid-template-columns: 1fr;
        }

        .kpi-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 760px) {
        .kpi-grid,
        .status-strip {
            grid-template-columns: 1fr;
        }

        .dashboard-actions {
            justify-content: stretch;
        }

        .button-flat {
            flex: 1;
        }

        .semaphore-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .bar-row {
            grid-template-columns: 82px minmax(0, 1fr) 36px;
        }

        .panel > div[style*="repeat(3"] {
            grid-template-columns: 1fr !important;
        }
    }
</style>

<div class="dashboard-shell">
    <div class="dashboard-hero">
        <section class="hero-panel">
            <div>
                <div style="font-size: 0.72rem; font-weight: 900; color: #93c5fd; text-transform: uppercase;">Dashboard ejecutivo</div>
                <h1 class="dashboard-title">{{ $project->name }}</h1>
                <p class="dashboard-subtitle">
                    Control de actualización documental, renovaciones, tiempos de aprobación, consultas técnicas y comunicación formal para {{ $project->code }}.
                </p>
                <div class="hero-meta">
                    <span class="hero-chip">Etapa: {{ $stageLabels[$project->project_stage] ?? 'Por definir' }}</span>
                    <span class="hero-chip">Prioridad: {{ $priorityLabels[$project->priority_level] ?? 'Sin prioridad' }}</span>
                    <span class="hero-chip">Responsable: {{ $project->manager->name ?? $project->owner->name ?? 'Sin asignar' }}</span>
                    <span class="hero-chip">Objetivo: {{ $project->target_date ? $project->target_date->format('d/m/Y') : 'Sin fecha' }}</span>
                </div>
            </div>
            <div class="dashboard-actions" style="margin-top: 1.25rem;">
                <a href="{{ route('projects.show', $project->id) }}" class="button-flat">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                    Proyecto
                </a>
                <a href="{{ route('projects.transmittals', $project->id) }}" class="button-flat">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4 20-7z"/></svg>
                    Transmittals
                </a>
                <a href="{{ route('projects.rfis', $project->id) }}" class="button-flat primary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    RFIs
                </a>
            </div>
        </section>

        <aside class="health-panel">
            <div class="panel-header" style="margin-bottom: 0.75rem;">
                <div>
                    <h2 class="panel-title">Salud de renovación</h2>
                    <div class="panel-kicker">Semáforo de archivos que deben mantenerse actualizados</div>
                </div>
                <span class="pill {{ $renewalSemaphore['status'] === 'red' ? 'danger' : ($renewalSemaphore['status'] === 'yellow' ? 'warning' : 'good') }}">
                    {{ $renewalSemaphore['status'] === 'red' ? 'Rojo' : ($renewalSemaphore['status'] === 'yellow' ? 'Amarillo' : 'Verde') }}
                </span>
            </div>
            <div class="renewal-signal">
                <div class="renewal-status-card status-{{ $renewalSemaphore['status'] }}">
                    <div class="renewal-status-row">
                        <div>
                            <div class="kpi-label">Nivel de riesgo</div>
                            <div style="font-size: 1.1rem; font-weight: 900; color: var(--text-main); margin-top: 0.3rem;">
                                {{ $renewalSemaphore['status'] === 'red' ? 'Crítico' : ($renewalSemaphore['status'] === 'yellow' ? 'Preventivo' : 'Controlado') }}
                            </div>
                            <div class="kpi-note">
                                {{ $renewalSemaphore['status'] === 'red' ? 'Hay renovaciones vencidas que requieren acción.' : ($renewalSemaphore['status'] === 'yellow' ? 'Hay archivos próximos a renovar o sin fecha.' : 'La carga renovable se mantiene vigente.') }}
                            </div>
                        </div>
                        <div class="renewal-score">
                            <strong>{{ $dashboardStats['renewal_compliance'] }}%</strong>
                            <span>cumplimiento</span>
                        </div>
                    </div>
                </div>
                <div class="kpi-note">{{ $dashboardStats['renewable_documents'] }} archivos con carga renovable monitoreada.</div>
                <div class="semaphore-bar">
                    <span style="width: {{ $renewalSemaphore['green'] > 0 ? max(4, round(($renewalSemaphore['green'] / $renewalSemaphore['total']) * 100)) : 0 }}%;" title="Vigentes"></span>
                    <span style="width: {{ $renewalSemaphore['yellow'] > 0 ? max(4, round(($renewalSemaphore['yellow'] / $renewalSemaphore['total']) * 100)) : 0 }}%;" title="Próximos"></span>
                    <span style="width: {{ $renewalSemaphore['red'] > 0 ? max(4, round(($renewalSemaphore['red'] / $renewalSemaphore['total']) * 100)) : 0 }}%;" title="Vencidos"></span>
                    <span style="width: {{ $renewalSemaphore['gray'] > 0 ? max(4, round(($renewalSemaphore['gray'] / $renewalSemaphore['total']) * 100)) : 0 }}%;" title="Sin fecha"></span>
                </div>
            </div>
            <div class="semaphore-grid">
                <div class="semaphore-count green">
                    <strong>{{ $renewalSemaphore['green'] }}</strong>
                    <span>Vigentes</span>
                </div>
                <div class="semaphore-count yellow">
                    <strong>{{ $renewalSemaphore['yellow'] }}</strong>
                    <span>Próximos</span>
                </div>
                <div class="semaphore-count red">
                    <strong>{{ $renewalSemaphore['red'] }}</strong>
                    <span>Vencidos</span>
                </div>
                <div class="semaphore-count gray">
                    <strong>{{ $renewalSemaphore['gray'] }}</strong>
                    <span>Sin fecha</span>
                </div>
            </div>
        </aside>
    </div>

    <section class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-topline">
                <div class="kpi-label">Archivos controlados</div>
                <div class="kpi-icon" style="background:#2563eb;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg></div>
            </div>
            <div>
                <div class="kpi-value">{{ $dashboardStats['total_documents'] }}</div>
                <div class="kpi-note">{{ $dashboardStats['documents_with_revision'] }} con revisión vigente registrada</div>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-topline">
                <div class="kpi-label">Flujos activos</div>
                <div class="kpi-icon" style="background:#7c3aed;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="6" cy="6" r="3"/><circle cx="18" cy="18" r="3"/><path d="M8.6 7.5c3.2 1.1 5.8 3.7 7 7"/></svg></div>
            </div>
            <div>
                <div class="kpi-value">{{ $dashboardStats['active_approvals'] }}</div>
                <div class="kpi-note">{{ $dashboardStats['average_flow_days'] ? $dashboardStats['average_flow_days'] . ' días promedio cerrados' : 'Sin cierres para promedio' }}</div>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-topline">
                <div class="kpi-label">RFIs abiertos</div>
                <div class="kpi-icon" style="background:#f97316;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></div>
            </div>
            <div>
                <div class="kpi-value">{{ $rfiStats['open'] + $rfiStats['pending'] }}</div>
                <div class="kpi-note">{{ $rfiStats['urgent'] }} urgentes, {{ $rfiStats['overdue'] }} vencidos</div>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-topline">
                <div class="kpi-label">Comunicación formal</div>
                <div class="kpi-icon" style="background:#0f766e;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4 20-7z"/></svg></div>
            </div>
            <div>
                <div class="kpi-value">{{ $dashboardStats['transmittals_total'] }}</div>
                <div class="kpi-note">{{ $dashboardStats['transmittals_30d'] }} transmittals en 30 días · {{ $emailStats['important'] }} correos importantes</div>
            </div>
        </div>
    </section>

    <div class="dashboard-grid">
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <section class="panel">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">Estado del acervo documental</h2>
                        <div class="panel-kicker">Distribución por etapa de revisión actual</div>
                    </div>
                </div>
                <div class="status-strip">
                    <div class="status-block">
                        <div class="kpi-label">Aprobados</div>
                        <div class="status-number" style="color:#059669;">{{ $statusBuckets['approved'] }}</div>
                        <div class="kpi-note">{{ round(($statusBuckets['approved'] / $totalStatus) * 100) }}% del total</div>
                    </div>
                    <div class="status-block">
                        <div class="kpi-label">En revisión</div>
                        <div class="status-number" style="color:#d97706;">{{ $statusBuckets['review'] }}</div>
                        <div class="kpi-note">{{ round(($statusBuckets['review'] / $totalStatus) * 100) }}% del total</div>
                    </div>
                    <div class="status-block">
                        <div class="kpi-label">Borrador</div>
                        <div class="status-number" style="color:#475569;">{{ $statusBuckets['draft'] }}</div>
                        <div class="kpi-note">{{ round(($statusBuckets['draft'] / $totalStatus) * 100) }}% del total</div>
                    </div>
                    <div class="status-block">
                        <div class="kpi-label">Otros estados</div>
                        <div class="status-number" style="color:#2563eb;">{{ $statusBuckets['other'] }}</div>
                        <div class="kpi-note">{{ round(($statusBuckets['other'] / $totalStatus) * 100) }}% del total</div>
                    </div>
                </div>
            </section>

            <section class="panel">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">Carga y actualización por semana</h2>
                        <div class="panel-kicker">Revisiones registradas durante las últimas 8 semanas</div>
                    </div>
                </div>
                <div class="trend-bars">
                    @foreach($revisionTrendData as $index => $value)
                        <div class="trend-column" title="{{ $value }} revisión(es)">
                            <div style="font-size: 0.68rem; font-weight: 900; color: var(--text-main);">{{ $value }}</div>
                            <div class="trend-bar" style="height: {{ max(4, round(($value / $maxTrend) * 140)) }}px;"></div>
                            <div class="trend-label">{{ $revisionTrendLabels[$index] }}</div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="panel">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">Disciplinas con mayor carga</h2>
                        <div class="panel-kicker">Archivos, renovables y vencidos por disciplina</div>
                    </div>
                </div>
                <div class="bar-list">
                    @forelse($disciplineBreakdown as $item)
                        <div class="bar-row">
                            <div title="{{ $item['name'] }}">{{ $item['prefix'] }}</div>
                            <div class="bar-track">
                                <div class="bar-fill" style="width: {{ round(($item['count'] / $maxDisciplineCount) * 100) }}%;"></div>
                            </div>
                            <div style="text-align:right;">{{ $item['count'] }}</div>
                            <div style="grid-column: 2 / 4; color: var(--text-muted); font-size: 0.68rem; font-weight: 700;">
                                {{ $item['renewables'] }} renovables · {{ $item['overdue'] }} vencidos
                            </div>
                        </div>
                    @empty
                        <div class="kpi-note">Aún no hay documentos para clasificar por disciplina.</div>
                    @endforelse
                </div>
            </section>
        </div>

        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <section class="panel">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">Señales ejecutivas</h2>
                        <div class="panel-kicker">Temas que conviene resolver primero</div>
                    </div>
                </div>
                <div class="alert-stack">
                    @forelse($executiveAlerts as $alert)
                        <div class="alert-row {{ $alert['level'] }}">
                            <div class="alert-title">{{ $alert['title'] }}</div>
                            <div class="alert-detail">{{ $alert['detail'] }}</div>
                        </div>
                    @empty
                        <div class="alert-row">
                            <div class="alert-title">Sin alertas críticas</div>
                            <div class="alert-detail">Renovaciones, RFIs y flujos no muestran bloqueos relevantes.</div>
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="panel">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">Próximas renovaciones</h2>
                        <div class="panel-kicker">Agenda de archivos que deben mantenerse actualizados</div>
                    </div>
                </div>
                <div class="queue-list">
                    @forelse($renewalQueue as $document)
                        @php
                            $dueDate = $document->renewal_due_date;
                            $isOverdue = $dueDate && $dueDate->isPast();
                        @endphp
                        <div class="queue-item">
                            <div>
                                <div class="queue-title">{{ $document->document_number }}</div>
                                <div class="queue-meta">{{ $document->title }}</div>
                                <div class="queue-meta">{{ $renewalFrequencyLabels[$document->renewal_frequency] ?? 'Renovable' }} · {{ $document->discipline->prefix ?? 'S/D' }}</div>
                            </div>
                            <span class="pill {{ $isOverdue ? 'danger' : 'warning' }}">
                                {{ $dueDate ? $dueDate->format('d/m/Y') : 'Sin fecha' }}
                            </span>
                        </div>
                    @empty
                        <div class="kpi-note">No hay archivos renovables programados.</div>
                    @endforelse
                </div>
            </section>

            <section class="panel">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">Flujos y RFIs en atención</h2>
                        <div class="panel-kicker">Pendientes con mayor impacto operativo</div>
                    </div>
                </div>
                <div class="queue-list">
                    @forelse($pendingApprovals as $approval)
                        <div class="queue-item">
                            <div>
                                <div class="queue-title">{{ $approval->fileRevision?->document?->document_number ?? 'Documento' }}</div>
                                <div class="queue-meta">{{ $approval->workflow?->name ?? 'Flujo de aprobación' }}</div>
                                <div class="queue-meta">Paso: {{ $approval->currentStep?->name ?? 'Sin paso' }} · {{ $approval->currentStep?->user?->name ?? 'Sin responsable' }}</div>
                            </div>
                            <span class="pill {{ $approval->updated_at->lt(now()->subDays(7)) ? 'danger' : 'warning' }}">{{ $approval->updated_at->diffForHumans() }}</span>
                        </div>
                    @empty
                        <div class="kpi-note">No hay aprobaciones activas.</div>
                    @endforelse

                    @foreach($openRfis as $rfi)
                        <div class="queue-item">
                            <div>
                                <div class="queue-title">{{ $rfi->number }} · {{ $rfi->subject }}</div>
                                <div class="queue-meta">Prioridad {{ $rfi->priority }} · vence {{ $rfi->due_date ? $rfi->due_date->format('d/m/Y') : 'sin fecha' }}</div>
                            </div>
                            <span class="pill {{ $rfi->priority === 'urgent' ? 'danger' : 'neutral' }}">{{ strtoupper($rfi->status) }}</span>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </div>

    <section class="panel">
        <div class="panel-header">
            <div>
                <h2 class="panel-title">Actividad reciente y trazabilidad</h2>
                <div class="panel-kicker">Últimos movimientos sobre proyecto y documentos</div>
            </div>
            <button onclick="exportAudit()" class="button-flat" style="min-height: 34px; padding: 0.5rem 0.75rem;">CSV</button>
        </div>
        <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem;">
            @forelse($recentActivity as $audit)
                <div class="activity-row">
                    <div class="avatar">{{ $audit->user ? strtoupper(substr($audit->user->name, 0, 2)) : 'SY' }}</div>
                    <div>
                        <div class="queue-title">{{ $audit->user?->name ?? 'Sistema' }}</div>
                        <div class="queue-meta">{{ $audit->details }}</div>
                        <div class="queue-meta">{{ $audit->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                </div>
            @empty
                <div class="kpi-note">No hay actividad reciente registrada.</div>
            @endforelse
        </div>
    </section>
</div>
@endsection

@section('scripts')
<script>
    function exportAudit() {
        const audits = {!! json_encode($readAudits) !!};
        const users = {!! json_encode($users) !!};
        let csvContent = "data:text/csv;charset=utf-8,";
        csvContent += "Fecha,Usuario,Email,Accion,Detalles,IP\n";

        audits.forEach(function(rowArray) {
            const user = users[rowArray.user_id];
            const userName = user ? user.name : 'Desconocido';
            const userEmail = user ? user.email : 'N/A';
            const details = String(rowArray.details || '').replace(/"/g, '""');
            const row = `"${rowArray.created_at}","${userName}","${userEmail}","${rowArray.action}","${details}","${rowArray.ip_address || ''}"`;
            csvContent += row + "\r\n";
        });

        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "auditoria_{{ $project->code }}.csv");
        document.body.appendChild(link);
        link.click();
        link.remove();
    }
</script>
@endsection
