@extends('layouts.public')

@section('title', 'Alertas de ofertas')

@section('content')
<style>
@import url('https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=Manrope:wght@400;500;600;700&display=swap');
:root {
    --fx-ink: #0f172a;
    --fx-ink-soft: #1f2937;
    --fx-muted: #64748b;
    --fx-primary: #2563eb;
    --fx-primary-strong: #1d4ed8;
    --fx-success: #16a34a;
    --fx-warning: #f59e0b;
    --fx-danger: #ef4444;
    --fx-surface: #ffffff;
    --fx-surface-soft: #f8fafc;
    --fx-bg: #eef2f7;
    --fx-border: rgba(15, 23, 42, 0.08);
    --fx-shadow-sm: 0 8px 20px rgba(15, 23, 42, 0.08);
    --fx-shadow-lg: 0 24px 55px rgba(15, 23, 42, 0.14);
}
/* Dark mode tokens (toggle via data-theme) */
[data-theme="dark"] {
    --fx-ink: #e2e8f0;
    --fx-ink-soft: #cbd5f5;
    --fx-muted: #94a3b8;
    --fx-primary: #60a5fa;
    --fx-primary-strong: #3b82f6;
    --fx-success: #22c55e;
    --fx-warning: #fbbf24;
    --fx-danger: #f87171;
    --fx-surface: #0f172a;
    --fx-surface-soft: #111827;
    --fx-bg: #0b1220;
    --fx-border: rgba(148, 163, 184, 0.18);
    --fx-shadow-sm: 0 12px 24px rgba(0, 0, 0, 0.35);
    --fx-shadow-lg: 0 32px 60px rgba(0, 0, 0, 0.5);
    --fx-glow: 0 0 0 1px rgba(96, 165, 250, 0.35);
}
[data-theme="dark"] body {
    background: var(--fx-bg);
    color: var(--fx-ink);
}
[data-theme="dark"] .wrapper,
[data-theme="dark"] .content-wrapper,
[data-theme="dark"] .content,
[data-theme="dark"] .content-header {
    background: var(--fx-bg);
    color: var(--fx-ink);
}
[data-theme="dark"] .text-muted { color: #a0aec0 !important; }
[data-theme="dark"] .bg-white { background: var(--fx-surface) !important; }
[data-theme="dark"] .border,
[data-theme="dark"] .border-top,
[data-theme="dark"] .border-bottom,
[data-theme="dark"] .border-left,
[data-theme="dark"] .border-right {
    border-color: var(--fx-border) !important;
}
[data-theme="dark"] .card {
    background: var(--fx-surface);
    border-color: var(--fx-border);
    color: var(--fx-ink);
}
[data-theme="dark"] .card-header,
[data-theme="dark"] .card-footer {
    border-color: rgba(148, 163, 184, 0.12);
}
.fx-page {
    background: radial-gradient(circle at top left, #f5f8ff 0%, #eef2f7 45%, #fef3e8 100%);
    padding-bottom: 32px;
}
[data-theme="dark"] .fx-page {
    background: radial-gradient(circle at top left, #0f172a 0%, #0b1220 45%, #111827 100%);
}
.fx-card {
    background: var(--fx-surface);
    border: 1px solid var(--fx-border);
    border-radius: 18px;
    box-shadow: var(--fx-shadow-sm);
}
[data-theme="dark"] .fx-card {
    box-shadow: var(--fx-shadow-sm);
}
.fx-header {
    padding: 24px;
    background: linear-gradient(135deg, rgba(37, 99, 235, 0.08), rgba(245, 158, 11, 0.08));
}
[data-theme="dark"] .fx-header {
    background: linear-gradient(135deg, rgba(15, 23, 42, 0.95), rgba(17, 24, 39, 0.95));
    border: 1px solid rgba(148, 163, 184, 0.2);
}
[data-theme="dark"] .fx-header .fx-stat {
    background: rgba(15, 23, 42, 0.9);
    border-color: rgba(148, 163, 184, 0.2);
}
[data-theme="dark"] .fx-kicker { color: #93c5fd; }
[data-theme="dark"] .page-title { color: #f8fafc; }
[data-theme="dark"] .page-subtitle { color: #cbd5f5; }
.fx-header-main {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 18px;
    flex-wrap: wrap;
}
.fx-header-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.fx-kicker {
    text-transform: uppercase;
    letter-spacing: 0.2em;
    font-size: 0.65rem;
    font-weight: 700;
    color: var(--fx-primary);
}
.page-title {
    font-family: 'Fraunces', serif;
    font-size: 1.9rem;
    font-weight: 700;
    color: var(--fx-ink);
}
.page-subtitle {
    font-family: 'Manrope', sans-serif;
    color: var(--fx-ink-soft);
    font-size: 1rem;
}
.fx-stat {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    border-radius: 14px;
    background: var(--fx-surface);
    border: 1px solid var(--fx-border);
}
.fx-stat i {
    font-size: 1.2rem;
    color: var(--fx-primary);
}
.fx-stat-label {
    font-size: 0.85rem;
    color: var(--fx-muted);
}
.fx-stat-value {
    font-weight: 700;
    color: var(--fx-ink);
}
.fx-section {
    margin-bottom: 28px;
}
.fx-section-title {
    font-family: 'Fraunces', serif;
    font-weight: 700;
    color: var(--fx-ink);
    font-size: 1.2rem;
}
.fx-section-subtitle {
    color: var(--fx-muted);
    font-size: 0.95rem;
    margin-bottom: 0;
}
.fx-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 0.8rem;
    background: rgba(37, 99, 235, 0.08);
    color: var(--fx-primary);
    font-weight: 600;
}
[data-theme="dark"] .fx-chip {
    background: rgba(96, 165, 250, 0.16);
    color: #bfdbfe;
}
.fx-empty {
    padding: 18px;
    border-radius: 14px;
    background: var(--fx-surface-soft);
    color: var(--fx-muted);
    text-align: center;
    border: 1px dashed var(--fx-border);
}
[data-theme="dark"] .fx-empty {
    background: rgba(15, 23, 42, 0.8);
}
.fx-form-card .card-header {
    background: linear-gradient(120deg, var(--fx-primary-strong), var(--fx-primary));
    color: #fff;
    border-bottom: none;
}
.fx-table thead th {
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--fx-muted);
    border-top: none;
}
[data-theme="dark"] .fx-table thead th { color: #c7d2fe; }
.fx-table tbody tr {
    border-bottom: 1px solid var(--fx-border);
}
[data-theme="dark"] .fx-table tbody tr {
    border-bottom: 1px solid rgba(148, 163, 184, 0.12);
}
[data-theme="dark"] .table-striped tbody tr:nth-of-type(odd) {
    background-color: rgba(148, 163, 184, 0.06);
}
[data-theme="dark"] .btn-outline-primary,
[data-theme="dark"] .btn-outline-secondary,
[data-theme="dark"] .btn-outline-info,
[data-theme="dark"] .btn-outline-danger {
    color: #e2e8f0;
    border-color: rgba(148, 163, 184, 0.35);
}
[data-theme="dark"] .btn-outline-primary:hover,
[data-theme="dark"] .btn-outline-secondary:hover,
[data-theme="dark"] .btn-outline-info:hover,
[data-theme="dark"] .btn-outline-danger:hover {
    background: rgba(148, 163, 184, 0.18);
}
[data-theme="dark"] .btn-light {
    background: rgba(148, 163, 184, 0.12);
    border-color: rgba(148, 163, 184, 0.2);
    color: #e2e8f0;
}
[data-theme="dark"] .fx-header .btn-outline-primary {
    color: #e2e8f0;
    border-color: rgba(148, 163, 184, 0.35);
}
[data-theme="dark"] .fx-header .btn-outline-primary:hover {
    background: rgba(148, 163, 184, 0.18);
}
[data-theme="dark"] .fx-header .btn-primary {
    color: #f8fafc;
}
[data-theme="dark"] .btn-primary {
    background: #3b82f6;
    border-color: #3b82f6;
    box-shadow: var(--fx-glow);
}
[data-theme="dark"] .btn-warning {
    background: #f59e0b;
    border-color: #f59e0b;
}
[data-theme="dark"] .form-control,
[data-theme="dark"] .input-group-text,
[data-theme="dark"] .custom-select {
    background: rgba(15, 23, 42, 0.95);
    border-color: rgba(148, 163, 184, 0.25);
    color: #e2e8f0;
}
[data-theme="dark"] .input-group-text { color: #cbd5f5; }
[data-theme="dark"] .form-control:focus {
    border-color: #60a5fa;
    box-shadow: 0 0 0 0.15rem rgba(96, 165, 250, 0.25);
}
[data-theme="dark"] .table { color: #e2e8f0; }
[data-theme="dark"] .alert {
    background: rgba(30, 41, 59, 0.85);
    border-color: rgba(148, 163, 184, 0.2);
    color: #e2e8f0;
}
[data-theme="dark"] .alert-warning {
    background: rgba(251, 191, 36, 0.16);
    color: #fde68a;
}
[data-theme="dark"] .alert-success {
    background: rgba(34, 197, 94, 0.16);
    color: #bbf7d0;
}
[data-theme="dark"] .alert-danger {
    background: rgba(248, 113, 113, 0.18);
    color: #fecaca;
}
[data-theme="dark"] .callout {
    background: rgba(15, 23, 42, 0.9);
    border-color: rgba(148, 163, 184, 0.2);
    color: #e2e8f0;
}
[data-theme="dark"] .callout a { color: #93c5fd; }
.fx-reveal {
    animation: fx-fade-up 0.6s ease both;
}
.fx-reveal-delay-1 { animation-delay: 0.08s; }
.fx-reveal-delay-2 { animation-delay: 0.16s; }
@keyframes fx-fade-up {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
}
@media (max-width: 767.98px) {
    .page-title { font-size: 1.4rem; }
    .page-subtitle { font-size: 0.95rem; }
}
</style>
<section class="content-header">
    <div class="container-fluid">
        @php
            $plan = $plan ?? 'free';
            $planLabels = ['free' => 'Free', 'basic' => 'Basico', 'pro' => 'Pro'];
            $currentPlanLabel = $planLabels[$plan] ?? ucfirst($plan);
        @endphp
        <div class="fx-card fx-header fx-reveal">
            <div class="fx-header-main">
                <div>
                    <div class="fx-kicker">Alertas inteligentes</div>
                    <h1 class="page-title"><i class="fas fa-tag text-primary"></i> Alertas de ofertas</h1>
                    <p class="page-subtitle mb-0">Monitorea precios y recibe alertas cuando bajen.</p>
                </div>
                <div class="fx-header-actions">
                    <a href="#offer-form" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Crear alerta</a>
                    <a href="#plans-section" class="btn btn-outline-primary"><i class="fas fa-credit-card"></i> Ver planes</a>
                    <button type="button" class="btn btn-light fx-theme-toggle" id="theme-toggle" aria-pressed="false">
                        <i class="fas fa-moon"></i> <span class="fx-theme-label">Modo oscuro</span>
                    </button>
                </div>
            </div>
            <div class="row mt-3 g-3">
                <div class="col-md-4">
                    <div class="fx-stat">
                        <i class="fas fa-bell"></i>
                        <div>
                            <div class="fx-stat-label">Alertas activas</div>
                            <div class="fx-stat-value">{{ $alerts->count() }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="fx-stat">
                        <i class="fas fa-store"></i>
                        <div>
                            <div class="fx-stat-label">Tiendas disponibles</div>
                            <div class="fx-stat-value">{{ count($stores) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="fx-stat">
                        <i class="fas fa-crown"></i>
                        <div>
                            <div class="fx-stat-label">Plan actual</div>
                            <div class="fx-stat-value">{{ $currentPlanLabel }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="content fx-page">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-8">
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        <h5 class="mb-2"><i class="icon fas fa-ban"></i> Revisa el formulario</h5>
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (session('success'))
                    <div class="alert alert-success alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        <i class="icon fas fa-check"></i> {{ session('success') }}
                    </div>
                @endif

                @if (session('warning'))
                    <div class="alert alert-warning alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        <i class="icon fas fa-exclamation-triangle"></i> {{ session('warning') }}
                    </div>
                @endif

                <div class="card fx-card fx-form-card" id="offer-form">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-plus-circle mr-1"></i> <span id="offer-form-title">Crear alerta</span></h3>
                    </div>
                    <div class="card-body">
                        @php
                            $currentPlan = $plan ?? 'free';
                        @endphp

                        @if($currentPlan === 'free')
                            <div class="callout callout-warning mb-3">
                                <h6 class="mb-1"><i class="fas fa-info-circle mr-1"></i> Plan Free</h6>
                                <ul class="mb-0 pl-3">
                                    <li>Máximo 2 alertas activas y 1 sola tienda.</li>
                                    <li>Solo email (1 correo por alerta).</li>
                                    <li>Ripley no disponible.</li>
                                </ul>
                            </div>
                        @elseif($currentPlan === 'basic')
                            <div class="callout callout-info mb-3">
                                <h6 class="mb-1"><i class="fas fa-info-circle mr-1"></i> Plan Email</h6>
                                <ul class="mb-0 pl-3">
                                    <li>Hasta 5 alertas activas.</li>
                                    <li>Correos ilimitados.</li>
                                    <li>Solo email.</li>
                                    <li>Ripley no disponible.</li>
                                </ul>
                            </div>
                        @else
                            <div class="callout callout-success mb-3">
                                <h6 class="mb-1"><i class="fas fa-info-circle mr-1"></i> Plan Pro</h6>
                                <ul class="mb-0 pl-3">
                                    <li>Hasta 15 alertas activas.</li>
                                    <li>Correos ilimitados.</li>
                                    <li>Email o WhatsApp.</li>
                                    <li>Ripley disponible.</li>
                                </ul>
                            </div>
                        @endif

                        @guest
                            <div class="callout callout-info mb-3">
                                <p class="mb-0">Tus alertas se guardan en este navegador (modo invitado). Usa el enlace “Administrar” para volver a verlas.</p>
                            </div>
                        @endguest

                        <form action="{{ route('offer-alerts.store') }}" method="POST" data-guest-consent="required" id="offer-alert-form">
                            @csrf
                            <input type="hidden" name="_method" id="offer-form-method" value="POST">
                            <input type="hidden" id="offer-alert-id" value="">

                            <div class="form-group">
                                <label>Link del producto</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-link"></i></span>
                                    </div>
                                    <input type="url" name="url" class="form-control" placeholder="https://tienda.com/producto" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Precio objetivo (opcional)</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">S/</span>
                                    </div>
                                    <input type="number" step="0.01" name="target_price" class="form-control" placeholder="Ej: 199.90">
                                </div>
                                <small class="text-muted">Si lo dejas vacío, puedes usar “Notificar ante cualquier baja”.</small>
                            </div>

                            <div class="form-group d-none" id="priceTypeGroup">
                                <label class="d-block" id="priceTypeLabel">Precio a monitorear</label>
                                <div class="custom-control custom-radio">
                                    <input class="custom-control-input" type="radio" name="price_type" id="price_public" value="public" checked>
                                    <label class="custom-control-label" for="price_public">Precio público</label>
                                </div>
                                <div class="custom-control custom-radio">
                                    <input class="custom-control-input" type="radio" name="price_type" id="price_cmr" value="cmr">
                                    <label class="custom-control-label" for="price_cmr" id="priceCardLabel">Precio tarjeta</label>
                                </div>
                            </div>

                            <div class="form-group mb-0">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="notify_on_any_drop" name="notify_on_any_drop" value="1">
                                    <label class="custom-control-label" for="notify_on_any_drop">Notificar ante cualquier baja de precio</label>
                                </div>
                            </div>

                            <hr>
                            <div class="form-group">
                                <label class="d-block mb-2">Canal de notificación</label>
                                <div class="btn-group btn-group-toggle d-flex" data-toggle="buttons">
                                    <label class="btn btn-outline-secondary active w-50" for="offer-channel-email">
                                        <input type="radio" name="channel" id="offer-channel-email" value="email" autocomplete="off" checked> <i class="fas fa-envelope mr-1"></i> Email
                                    </label>
                                    <label class="btn btn-outline-success w-50 {{ !($canUseWhatsApp ?? false) ? 'disabled' : '' }}" for="offer-channel-whatsapp">
                                        <input type="radio" name="channel" id="offer-channel-whatsapp" value="whatsapp" autocomplete="off" {{ !($canUseWhatsApp ?? false) ? 'disabled' : '' }}> <i class="fab fa-whatsapp mr-1"></i> WhatsApp
                                    </label>
                                </div>
                                @unless($canUseWhatsApp ?? false)
                                    <small class="text-muted d-block mt-1">WhatsApp disponible solo en plan Pro.</small>
                                @endunless
                            </div>

                            <div class="form-group">
                                <label>Teléfono / WhatsApp (opcional)</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    </div>
                                    <select class="custom-select" id="offer-phone-country" aria-label="Codigo de pais">
                                        <option value="+51">+51 Peru</option>
                                    </select>
                                    <input type="tel" name="contact_phone" class="form-control" placeholder="999 999 999" inputmode="numeric">
                                </div>
                                <small class="text-muted">Requerido si eliges WhatsApp.</small>
                            </div>

                            @guest
                                <hr>
                                <div class="form-group">
                                    <label>Email</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                        </div>
                                        <input type="email" name="contact_email" class="form-control" placeholder="tu@email.com" required>
                                    </div>
                                </div>
                            @endguest

                            <div class="d-flex justify-content-end">
                                <button class="btn btn-outline-secondary d-none mr-2" type="button" id="offer-cancel-edit">
                                    <i class="fas fa-times mr-1"></i> Cancelar edicion
                                </button>
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-save mr-1"></i> <span id="offer-submit-text">Guardar alerta</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card fx-card fx-table-card mt-3">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-list mr-1"></i> Mis alertas</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive d-none d-md-block">
                            <table class="table mb-0 fx-table">
                                <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Tienda</th>
                                    <th class="text-nowrap">Actual</th>
                                    <th class="text-nowrap">Objetivo</th>
                                    <th>Estado</th>
                                    <th class="text-right">Acciones</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($alerts as $alert)
                                    <tr data-alert-id="{{ $alert->id }}">
@php
                                            // Lógica mejorada para mostrar precio disponible según lo que tenga el producto
                                            if ($alert->price_type === 'cmr') {
                                                $displayPrice = $alert->cmr_price;
                                                $priceUnavailable = $displayPrice === null;
                                                $priceTypeLabel = 'CMR';
                                                
                                                // Si no hay precio CMR, mostrar automáticamente el precio público
                                                if ($priceUnavailable && $alert->public_price !== null) {
                                                    $displayPrice = $alert->public_price;
                                                    $priceUnavailable = false;
                                                    $priceTypeLabel = 'Público (fallback)';
                                                }
                                            } else {
                                                $displayPrice = $alert->public_price;
                                                $priceUnavailable = $displayPrice === null;
                                                $priceTypeLabel = 'Público';
                                            }
                                            
                                            // Fallback final solo para mostrar algo
                                            if ($displayPrice === null && $alert->current_price !== null) {
                                                $displayPrice = $alert->current_price;
                                                $priceUnavailable = false;
                                                $priceTypeLabel = 'Actual';
                                            }
                                        @endphp
                                        <td class="align-middle">
                                            <div class="font-weight-bold">{{ $alert->title ?? 'Producto' }}</div>
                                            <div class="small">
                                                <a href="{{ $alert->url }}" target="_blank" rel="noopener">Ver producto</a>
                                                @guest
                                                    @if($alert->public_token)
                                                        <span class="mx-1 text-muted">·</span>
                                                        <a href="{{ route('offer-alerts.public.show', $alert->public_token) }}">Administrar</a>
                                                    @endif
                                                @endguest
                                                @auth
                                                    @if($alert->user_id === auth()->id())
                                                        <span class="mx-1 text-muted">·</span>
                                                        <a href="{{ route('offer-alerts.show', $alert) }}">Detalle</a>
                                                    @endif
                                                @endauth
                                            </div>
                                        </td>
                                        <td class="align-middle text-uppercase">
                                            <span class="badge badge-info">{{ $alert->store ?? '-' }}</span>
                                        </td>
                                        <td class="align-middle text-nowrap">
                                            @if($priceUnavailable)
                                                <span class="text-muted">No disponible</span>
                                            @else
                                                <div>S/ {{ number_format($displayPrice, 2) }}</div>
                                                @if(isset($priceTypeLabel) && $priceTypeLabel !== 'Público')
                                                    <small class="text-muted">{{ $priceTypeLabel }}</small>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="align-middle text-nowrap">{{ $alert->target_price ? 'S/ '.number_format($alert->target_price, 2) : '-' }}</td>
                                        <td class="align-middle">
                                            @php
                                                $statusClass = match ($alert->status) {
                                                    'active' => 'primary',
                                                    'fallback_email' => 'info',
                                                    'inactive' => 'secondary',
                                                    'triggered' => 'success',
                                                    default => 'info',
                                                };
                                            @endphp
                                            <span class="badge badge-{{ $statusClass }}">{{ $alert->status }}</span>
                                        </td>
                                        <td class="align-middle text-right">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a class="btn btn-outline-primary" href="{{ $alert->url }}" target="_blank" rel="noopener" title="Abrir">
                                                    <i class="fas fa-external-link-alt"></i>
                                                </a>
                                                <button class="btn btn-outline-info offer-alert-edit-btn" type="button" title="Editar"
                                                    data-id="{{ $alert->id }}"
                                                    data-url="{{ $alert->url }}"
                                                    data-target-price="{{ $alert->target_price }}"
                                                    data-notify="{{ $alert->notify_on_any_drop ? 1 : 0 }}"
                                                    data-price-type="{{ $alert->price_type }}"
                                                    data-channel="{{ $alert->channel }}"
                                                    data-contact-email="{{ $alert->contact_email }}"
                                                    data-contact-phone="{{ $alert->contact_phone }}">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form action="{{ route('offer-alerts.destroy', $alert) }}" method="POST" class="d-inline offer-alert-delete-form" data-alert-id="{{ $alert->id }}">
                                                    @csrf @method('DELETE')
                                                    <button class="btn btn-outline-danger" type="submit" title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-3">No tienes alertas aún.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="d-block d-md-none p-3">
                            @forelse($alerts as $alert)
                                @php
                                    $statusClass = match ($alert->status) {
                                        'active' => 'primary',
                                        'fallback_email' => 'info',
                                        'inactive' => 'secondary',
                                        'triggered' => 'success',
                                        default => 'info',
                                    };
if ($alert->price_type === 'cmr') {
                                        $displayPrice = $alert->cmr_price;
                                        $priceUnavailable = $displayPrice === null;
                                    } else {
                                        $displayPrice = $alert->public_price;
                                        $priceUnavailable = $displayPrice === null;
                                    }
                                    
                                    // Fallback solo para mostrar algo, no para reemplazar precio específico
                                    if ($displayPrice === null) {
                                        $displayPrice = $alert->current_price;
                                    }
                                @endphp
                                <div class="card fx-card mb-3" data-alert-id="{{ $alert->id }}">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div class="pr-3">
                                                <div class="font-weight-bold">{{ $alert->title ?? 'Producto' }}</div>
                                                <div class="text-muted small text-uppercase">{{ $alert->store ?? '-' }}</div>
                                            </div>
                                            <span class="badge badge-{{ $statusClass }} align-self-start">{{ $alert->status }}</span>
                                        </div>

                                        <div class="row mt-2">
                                            <div class="col-6">
                                                <small class="text-muted d-block">Actual</small>
                                                <div class="font-weight-bold">
                                                    @if($priceUnavailable)
                                                        <span class="text-muted">No disponible</span>
                                                    @else
                                                        S/ {{ number_format($displayPrice, 2) }}
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted d-block">Objetivo</small>
                                                <div class="font-weight-bold">{{ $alert->target_price ? 'S/ '.number_format($alert->target_price, 2) : '-' }}</div>
                                            </div>
                                        </div>

                                        <div class="mt-3 d-flex justify-content-between align-items-center">
                                            <a href="{{ $alert->url }}" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener">
                                                <i class="fas fa-external-link-alt mr-1"></i> Ver
                                            </a>
                                            <button class="btn btn-sm btn-outline-info offer-alert-edit-btn" type="button"
                                                data-id="{{ $alert->id }}"
                                                data-url="{{ $alert->url }}"
                                                data-target-price="{{ $alert->target_price }}"
                                                data-notify="{{ $alert->notify_on_any_drop ? 1 : 0 }}"
                                                data-price-type="{{ $alert->price_type }}"
                                                data-channel="{{ $alert->channel }}"
                                                data-contact-email="{{ $alert->contact_email }}"
                                                data-contact-phone="{{ $alert->contact_phone }}">
                                                <i class="fas fa-edit mr-1"></i> Editar
                                            </button>
                                            @guest
                                                @if($alert->public_token)
                                                    <a href="{{ route('offer-alerts.public.show', $alert->public_token) }}" class="btn btn-sm btn-outline-secondary">Administrar</a>
                                                @endif
                                            @endguest
                                            <form action="{{ route('offer-alerts.destroy', $alert) }}" method="POST" class="d-inline offer-alert-delete-form" data-alert-id="{{ $alert->id }}">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger" type="submit">
                                                    <i class="fas fa-trash mr-1"></i> Eliminar
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center text-muted py-3">No tienes alertas aún.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card fx-card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-store mr-1"></i> Tiendas disponibles</h3>
                    </div>
                    <div class="card-body d-flex flex-wrap">
                        @foreach($stores as $store)
                            @php $isRipley = strtolower($store) === 'ripley'; @endphp
                            @if($isRipley && !($canUseRipley ?? false))
                                <span class="badge badge-secondary mr-2 mb-2 text-uppercase" title="Ripley solo en plan Pro">{{ $store }}</span>
                            @else
                                <span class="badge badge-info mr-2 mb-2 text-uppercase">{{ $store }}</span>
                            @endif
                        @endforeach
                    </div>
                    <div class="card-footer">
                        <small class="text-muted">Tip: pega el enlace directo del producto.</small>
                    </div>
                </div>
                @if (!empty($utility))
                    <div class="fx-section fx-reveal mt-3" id="plans-section">
                        <div class="d-flex justify-content-between align-items-end flex-wrap mb-2">
                            <div>
                                <h2 class="fx-section-title mb-1"><i class="fas fa-credit-card"></i> Planes y pagos</h2>
                                <p class="fx-section-subtitle">Compara beneficios y activa el plan que necesitas.</p>
                            </div>
                            <span class="fx-chip"><i class="fas fa-shield-alt"></i> Pago seguro</span>
                        </div>
                        @include('modules.core.partials.mercadopago_plans', [
                            'utility' => $utility,
                            'planDetails' => [
                                'basic' => [
                                    'Hasta 5 alertas activas',
                                    'Notificaciones por email',
                                    'Sin Ripley',
                                ],
                                'pro' => [
                                    'Hasta 15 alertas activas',
                                    'WhatsApp y email',
                                    'Incluye Ripley',
                                ],
                            ],
                        ])
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

    <div class="fx-comments fx-reveal">
        @include('modules.core.partials.comments', ['utility' => $utility, 'comments' => $comments])
    </div>

@push('page_scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const themeToggle = document.getElementById('theme-toggle');
    const themeStorageKey = 'fx-theme';
    const root = document.documentElement;

    const applyTheme = (isDark) => {
        if (isDark) {
            root.setAttribute('data-theme', 'dark');
        } else {
            root.removeAttribute('data-theme');
        }
        if (themeToggle) {
            themeToggle.setAttribute('aria-pressed', isDark ? 'true' : 'false');
            const label = themeToggle.querySelector('.fx-theme-label');
            if (label) label.textContent = isDark ? 'Modo claro' : 'Modo oscuro';
            const icon = themeToggle.querySelector('i');
            if (icon) icon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
        }
    };

    if (themeToggle) {
        const storedTheme = localStorage.getItem(themeStorageKey);
        applyTheme(storedTheme === 'dark');
        themeToggle.addEventListener('click', () => {
            const isDark = root.getAttribute('data-theme') === 'dark';
            const nextIsDark = !isDark;
            localStorage.setItem(themeStorageKey, nextIsDark ? 'dark' : 'light');
            applyTheme(nextIsDark);
        });
    }

    const urlInput = document.querySelector('input[name="url"]');
    const group = document.getElementById('priceTypeGroup');
    const groupLabel = document.getElementById('priceTypeLabel');
    const publicRadio = document.getElementById('price_public');
    const cmrRadio = document.getElementById('price_cmr');
    const cardLabel = document.getElementById('priceCardLabel');

    const checkCardPriceAvailability = async () => {
        const val = (urlInput.value || '').toLowerCase();
        const isRipley = val.includes('ripley');
        
        // Remover advertencias anteriores
        const existingWarning = document.getElementById('card-price-warning');
        if (existingWarning) {
            existingWarning.remove();
        }
        
        // Solo verificar para Ripley por ahora
        if (!isRipley || val.trim() === '') {
            return;
        }
        
        try {
            // Mostrar indicador de carga
            const indicator = document.createElement('span');
            indicator.id = 'card-price-checking';
            indicator.className = 'text-muted small ml-2';
            indicator.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando precio de tarjeta...';
            
            if (cardLabel && !document.getElementById('card-price-checking')) {
                cardLabel.parentNode.appendChild(indicator);
            }
            
            // Pequeña demora para evitar demasiadas llamadas
            await new Promise(resolve => setTimeout(resolve, 500));
            
            // Remover indicador
            if (indicator) {
                indicator.remove();
            }
            
            // Eliminar advertencia preventiva para permitir funcionamiento normal con precios CMR
        } catch (error) {
            console.log('No se pudo verificar disponibilidad de precio de tarjeta');
            // Remover indicador en caso de error
            const indicator = document.getElementById('card-price-checking');
            if (indicator) {
                indicator.remove();
            }
        }
    };

    function togglePriceType() {
        const val = (urlInput.value || '').toLowerCase();
        const isFalabella = val.includes('falabella');
        const isRipley = val.includes('ripley');
        const isOechsle = val.includes('oechsle');
        const isSodimac = val.includes('sodimac');
        const isPromart = val.includes('promart');
        if (isFalabella || isRipley || isOechsle || isSodimac || isPromart) {
            group.classList.remove('d-none');
            if (groupLabel) {
                groupLabel.textContent = isRipley
                    ? 'Precio a monitorear (Ripley)'
                    : (isOechsle
                        ? 'Precio a monitorear (Oechsle)'
                        : (isSodimac
                            ? 'Precio a monitorear (Sodimac)'
                            : (isPromart ? 'Precio a monitorear (Promart)' : 'Precio a monitorear (Falabella)')));
            }
            if (cardLabel) {
                cardLabel.textContent = isRipley
                    ? 'Precio Tarjeta Ripley'
                    : (isOechsle || isPromart ? 'Precio Tarjeta Oh' : (isSodimac ? 'Precio Única/CMR' : 'Precio CMR'));
            }
            
            // Verificar disponibilidad de precio de tarjeta para Ripley
            if (isRipley) {
                checkCardPriceAvailability();
            }
        } else {
            group.classList.add('d-none');
            publicRadio.checked = true;
            cmrRadio.checked = false;
            
            // Limpiar advertencias al cambiar de URL
            const existingWarning = document.getElementById('card-price-warning');
            if (existingWarning) {
                existingWarning.remove();
            }
        }
    }

    urlInput.addEventListener('input', togglePriceType);
    togglePriceType();

    const emailRadio = document.getElementById('offer-channel-email');
    const whatsappRadio = document.getElementById('offer-channel-whatsapp');
    const phoneInput = document.querySelector('input[name="contact_phone"]');
    const phoneCountry = document.getElementById('offer-phone-country');
    const emailInput = document.querySelector('input[name="contact_email"]');
    const phoneGroup = phoneInput ? phoneInput.closest('.form-group') : null;
    const emailGroup = emailInput ? emailInput.closest('.form-group') : null;
    const emailStorageKey = 'offer_alert_email';
    const phoneStorageKey = 'offer_alert_phone';
    const storedEmail = emailInput ? localStorage.getItem(emailStorageKey) : null;
    const storedPhone = phoneInput ? localStorage.getItem(phoneStorageKey) : null;
    const lastPhoneFromServer = @json($lastPhone ?? null);
    if (emailInput && !emailInput.value && storedEmail) {
        emailInput.value = storedEmail;
    }
    const normalizePhone = (value) => (value || '').replace(/\s+/g, '');
    const fillPhoneInput = (value) => {
        if (!phoneInput) return;
        const clean = normalizePhone(value);
        const code = '+51';
        if (phoneCountry) phoneCountry.value = code;
        if (clean.startsWith(code)) {
            phoneInput.value = clean.slice(code.length).trim();
            return;
        }
        phoneInput.value = clean.replace(/^\+/, '');
    };
    const buildPhoneValue = () => {
        const code = phoneCountry ? phoneCountry.value : '+51';
        const raw = normalizePhone(phoneInput ? phoneInput.value : '');
        if (!raw) return '';
        if (raw.startsWith('+')) return raw;
        return `${code}${raw}`;
    };
    const initialPhone = storedPhone || lastPhoneFromServer || '';
    if (phoneInput && initialPhone) {
        fillPhoneInput(initialPhone);
    }

    const applyChannelVisibility = () => {
        const isWhatsapp = !!(whatsappRadio && whatsappRadio.checked && !whatsappRadio.disabled);
        if (phoneGroup) {
            phoneGroup.classList.toggle('d-none', !isWhatsapp);
            phoneGroup.hidden = !isWhatsapp;
        }
        if (emailGroup) {
            emailGroup.classList.toggle('d-none', isWhatsapp);
            emailGroup.hidden = isWhatsapp;
        }
        if (phoneInput) {
            phoneInput.required = isWhatsapp;
            phoneInput.disabled = !isWhatsapp;
        }
        if (emailInput) {
            emailInput.required = !isWhatsapp;
            emailInput.disabled = isWhatsapp;
            if (isWhatsapp) {
                emailInput.dataset.lastValue = emailInput.value;
                emailInput.value = '';
            } else if (!emailInput.value) {
                emailInput.value = emailInput.dataset.lastValue || storedEmail || '';
            }
        }
    };

    applyChannelVisibility();
    if (emailRadio) emailRadio.addEventListener('change', applyChannelVisibility);
    if (whatsappRadio) whatsappRadio.addEventListener('change', applyChannelVisibility);
    if (emailInput) {
        emailInput.addEventListener('change', () => {
            const value = (emailInput.value || '').trim();
            if (value) {
                localStorage.setItem(emailStorageKey, value);
            }
        });
    }
    if (phoneInput) {
        phoneInput.addEventListener('change', () => {
            const value = buildPhoneValue();
            if (value) {
                localStorage.setItem(phoneStorageKey, value);
            }
        });
    }
    if (phoneCountry) {
        phoneCountry.addEventListener('change', () => {
            const value = buildPhoneValue();
            if (value) {
                localStorage.setItem(phoneStorageKey, value);
            }
        });
    }

    const offerForm = document.getElementById('offer-alert-form');
    const methodInput = document.getElementById('offer-form-method');
    const alertIdInput = document.getElementById('offer-alert-id');
    const formTitle = document.getElementById('offer-form-title');
    const submitText = document.getElementById('offer-submit-text');
    const cancelBtn = document.getElementById('offer-cancel-edit');
    const defaultAction = "{{ route('offer-alerts.store') }}";
    const updateBaseUrl = "{{ url('/offer-alerts') }}";
    const targetInput = document.querySelector('input[name="target_price"]');
    const notifyInput = document.getElementById('notify_on_any_drop');
    const priceTypeInputs = document.querySelectorAll('input[name="price_type"]');
    const defaultEmail = emailInput ? (emailInput.value || storedEmail || '') : '';
    const defaultPhone = storedPhone || lastPhoneFromServer || '';
    const defaultUrl = urlInput ? urlInput.value : '';

    const setReadOnlyFields = (isEditing) => {
        if (urlInput) {
            urlInput.readOnly = isEditing;
        }
        priceTypeInputs.forEach((input) => {
            input.disabled = isEditing;
        });
    };

    const resetOfferForm = () => {
        if (!offerForm) return;
        offerForm.reset();
        offerForm.action = defaultAction;
        if (methodInput) methodInput.value = 'POST';
        if (alertIdInput) alertIdInput.value = '';
        if (formTitle) formTitle.textContent = 'Crear alerta';
        if (submitText) submitText.textContent = 'Guardar alerta';
        if (cancelBtn) cancelBtn.classList.add('d-none');
        if (urlInput) {
            urlInput.value = defaultUrl || '';
        }
        if (emailInput && defaultEmail) {
            emailInput.value = defaultEmail;
        }
        if (phoneInput) {
            if (defaultPhone) {
                fillPhoneInput(defaultPhone);
            } else {
                phoneInput.value = '';
            }
        }
        setReadOnlyFields(false);
        togglePriceType();
        applyChannelVisibility();
    };

    const enterEditMode = (data) => {
        if (!offerForm || !data?.id) return;
        if (alertIdInput) alertIdInput.value = data.id;
        offerForm.action = `${updateBaseUrl}/${data.id}`;
        if (methodInput) methodInput.value = 'PATCH';
        if (urlInput) {
            urlInput.value = data.url || '';
        }
        if (targetInput) {
            targetInput.value = data.targetPrice || '';
        }
        if (notifyInput) {
            notifyInput.checked = data.notify === '1' || data.notify === 1;
        }
        if (priceTypeInputs.length) {
            const desired = data.priceType || 'public';
            priceTypeInputs.forEach((input) => {
                input.checked = input.value === desired;
            });
        }
        const channel = data.channel || 'email';
        if (emailRadio && channel === 'email') {
            emailRadio.checked = true;
        }
        if (whatsappRadio && channel === 'whatsapp' && !whatsappRadio.disabled) {
            whatsappRadio.checked = true;
        }
        applyChannelVisibility();
        if (emailInput) {
            emailInput.value = data.contactEmail || '';
        }
        if (phoneInput) {
            fillPhoneInput(data.contactPhone || '');
        }
        setReadOnlyFields(true);
        togglePriceType();
        if (formTitle) formTitle.textContent = 'Editar alerta';
        if (submitText) submitText.textContent = 'Actualizar alerta';
        if (cancelBtn) cancelBtn.classList.remove('d-none');
        offerForm.scrollIntoView({ behavior: 'smooth' });
    };

    if (cancelBtn) {
        cancelBtn.addEventListener('click', resetOfferForm);
    }

    if (offerForm) {
        offerForm.addEventListener('submit', () => {
            const isWhatsapp = !!(whatsappRadio && whatsappRadio.checked && !whatsappRadio.disabled);
            if (isWhatsapp && phoneInput) {
                const full = buildPhoneValue();
                if (full) {
                    phoneInput.value = full;
                }
            }
        });
    }

    document.addEventListener('click', (event) => {
        const btn = event.target.closest('.offer-alert-edit-btn');
        if (!btn) return;
        const data = {
            id: btn.dataset.id,
            url: btn.dataset.url,
            targetPrice: btn.dataset.targetPrice,
            notify: btn.dataset.notify,
            priceType: btn.dataset.priceType,
            channel: btn.dataset.channel,
            contactEmail: btn.dataset.contactEmail,
            contactPhone: btn.dataset.contactPhone,
        };
        enterEditMode(data);
    });

    // Event listener para cambios en tipo de precio
    document.addEventListener('change', (event) => {
        if (event.target && event.target.name === 'price_type') {
            // Limpiar advertencia cuando el usuario cambia de tipo de precio
            const existingWarning = document.getElementById('card-price-warning');
            if (existingWarning) {
                existingWarning.remove();
            }
        }
    });
    document.addEventListener('submit', async function (e) {
        const form = e.target;
        if (!form.classList.contains('offer-alert-delete-form')) return;
        e.preventDefault();
        const action = form.getAttribute('action');
        const alertId = form.dataset.alertId;
        if (!action) return;
        if (!confirm('¿Eliminar alerta?')) return;
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        try {
            const res = await fetch(action, {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': token || '',
                },
            });
if (!res.ok) throw new Error('No se pudo eliminar la alerta.');
            const row = form.closest('tr') || (alertId ? document.querySelector(`tr[data-alert-id="${alertId}"]`) : null);
            if (row) row.remove();
            const card = form.closest('.card') || (alertId ? document.querySelector(`.card[data-alert-id="${alertId}"]`) : null);
            if (card) card.remove();
            
            // Actualizar contador de alertas en el header
            const alertCounter = document.querySelector('.fx-stat-value');
            if (alertCounter) {
                const currentCount = parseInt(alertCounter.textContent) || 0;
                const newCount = Math.max(0, currentCount - 1);
                alertCounter.textContent = newCount;
            }
            
            // Mostrar mensaje de éxito
            const alertHtml = `
                <div class="alert alert-success alert-dismissible fade show mb-3">
                    <button type="button" class="close" data-dismiss="alert">×</button>
                    <i class="fas fa-check"></i> Alerta eliminada exitosamente
                </div>
            `;
            const headerContainer = document.querySelector('.content-header .container-fluid');
            if (headerContainer) {
                // Eliminar alertas anteriores para evitar acumulación
                const existingAlerts = headerContainer.querySelectorAll('.alert');
                existingAlerts.forEach(alert => alert.remove());
                headerContainer.insertAdjacentHTML('beforeend', alertHtml);
            }
        } catch (error) {
            console.error('Error eliminando alerta', error);
        }
    });
});
</script>
@endpush
@endsection




