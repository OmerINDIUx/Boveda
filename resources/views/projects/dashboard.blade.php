@extends('layouts.app')

@section('title', 'Dashboard de Control')

@section('content')
<div class="project-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h1 style="font-weight: 800; font-size: 2.5rem; letter-spacing: -1px; margin: 0;">Dashboard de <span style="color: var(--primary);">Control</span></h1>
        <p style="color: var(--text-muted);">KPIs y Analíticas para {{ $project->code }}</p>
    </div>
    <div style="display: flex; gap: 1rem;">
        <a href="{{ route('projects.show', $project->id) }}" class="btn-secondary" style="text-decoration: none; display: flex; align-items: center; gap: 0.5rem; padding: 0.8rem 1.5rem; border-radius: 12px; font-weight: 700;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Volver al Proyecto
        </a>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 2rem;">
    <!-- RFI Stats -->
    <div class="glass-card" style="border-left: 4px solid #ef4444;">
        <h3 style="font-size: 0.8rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">RFIs Urgentes / Pendientes</h3>
        <div style="font-size: 2.5rem; font-weight: 800; color: #0f172a; margin-top: 0.5rem;">{{ $rfiStats['pending'] }}</div>
    </div>
    <div class="glass-card" style="border-left: 4px solid #3b82f6;">
        <h3 style="font-size: 0.8rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">RFIs Abiertos</h3>
        <div style="font-size: 2.5rem; font-weight: 800; color: #0f172a; margin-top: 0.5rem;">{{ $rfiStats['open'] }}</div>
    </div>
    <div class="glass-card" style="border-left: 4px solid #10b981;">
        <h3 style="font-size: 0.8rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">RFIs Cerrados</h3>
        <div style="font-size: 2.5rem; font-weight: 800; color: #0f172a; margin-top: 0.5rem;">{{ $rfiStats['closed'] }}</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
    <!-- S-Curve Chart -->
    <div class="glass-card">
        <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--text-main); margin-bottom: 1.5rem;">Curva S de Avance Documental</h3>
        <div style="position: relative; height: 300px; width: 100%;">
            <canvas id="sCurveChart"></canvas>
        </div>
    </div>

    <!-- Extended Audit Trail -->
    <div class="glass-card" style="display: flex; flex-direction: column;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--text-main);">Pista de Auditoría Extendida</h3>
            <button onclick="exportAudit()" class="btn-secondary" style="font-size: 0.7rem; padding: 0.4rem 0.8rem;">Descargar CSV</button>
        </div>
        <div style="flex: 1; overflow-y: auto; max-height: 300px; display: flex; flex-direction: column; gap: 1rem;">
            @forelse($readAudits as $audit)
                <div style="display: flex; gap: 0.75rem; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem;">
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: #eef2ff; color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 800; flex-shrink: 0;">
                        {{ $audit->user ? substr($audit->user->name, 0, 2) : 'SYS' }}
                    </div>
                    <div>
                        <div style="font-weight: 700; font-size: 0.8rem; color: var(--text-main);">{{ $audit->user ? $audit->user->name : 'Usuario Desconocido' }}</div>
                        <div style="font-size: 0.7rem; color: var(--text-muted); line-height: 1.4;">{{ $audit->details }}</div>
                        <div style="font-size: 0.65rem; color: #94a3b8; margin-top: 0.25rem;">{{ $audit->created_at->format('d/m/Y H:i:s') }}</div>
                    </div>
                </div>
            @empty
                <p style="font-size: 0.8rem; color: var(--text-muted); text-align: center;">No hay registros de auditoría recientes.</p>
            @endforelse
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('sCurveChart').getContext('2d');
        const labels = {!! json_encode($sCurveLabels) !!};
        const dataPoints = {!! json_encode($sCurveData) !!};

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Documentos Acumulados',
                    data: dataPoints,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.05)' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    });

    function exportAudit() {
        // Simple CSV export logic
        const audits = {!! json_encode($readAudits) !!};
        const users = {!! json_encode($users) !!};
        
        let csvContent = "data:text/csv;charset=utf-8,";
        csvContent += "Fecha,Usuario,Email,Accion,Detalles,IP\n";

        audits.forEach(function(rowArray) {
            let user = users[rowArray.user_id];
            let userName = user ? user.name : 'Desconocido';
            let userEmail = user ? user.email : 'N/A';
            let row = `"${rowArray.created_at}","${userName}","${userEmail}","${rowArray.action}","${rowArray.details}","${rowArray.ip_address}"`;
            csvContent += row + "\r\n";
        });

        var encodedUri = encodeURI(csvContent);
        var link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "auditoria_{{ $project->code }}.csv");
        document.body.appendChild(link);
        link.click();
    }
</script>
@endsection
