@extends('layouts.app')

@section('title', 'Nuevo Flujo')

@section('content')
<div class="top-header">
    <div>
        <h1 style="font-size: 2.2rem; letter-spacing: -1px; color: var(--text-main);">Nuevo <span style="color: var(--primary);">Flujo</span></h1>
        <p style="color: var(--text-muted); font-weight: 600;">Define alcance, niveles y responsables de aprobación.</p>
    </div>
    <a href="{{ route('workflows.index') }}" class="btn-secondary">Volver</a>
</div>

@include('workflows._form', [
    'action' => route('workflows.store'),
    'method' => 'POST',
])
@endsection
