@extends('layouts.public')

@section('title', 'Alerta de Divisas')

@section('content')
<style>
@import url('https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=Manrope:wght@400;500;600;700&display=swap');
/* Mini design system tokens */
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
[data-theme="dark"] .content-header,
[data-theme="dark"] .content {
    color: var(--fx-ink);
}
[data-theme="dark"] .text-muted {
    color: #a0aec0 !important;
}
[data-theme="dark"] .text-dark {
    color: var(--fx-ink) !important;
}
[data-theme="dark"] .bg-white {
    background: var(--fx-surface) !important;
}
[data-theme="dark"] .border,
[data-theme="dark"] .border-top,
[data-theme="dark"] .border-bottom,
[data-theme="dark"] .border-left,
[data-theme="dark"] .border-right {
    border-color: var(--fx-border) !important;
}
[data-theme="dark"] .small {
    color: #cbd5f5;
}
[data-theme="dark"] a {
    color: #93c5fd;
}
[data-theme="dark"] a:hover {
    color: #bfdbfe;
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
[data-theme="dark"] .fx-kicker {
    color: #93c5fd;
}
[data-theme="dark"] .page-title {
    color: #f8fafc;
}
[data-theme="dark"] .page-subtitle {
    color: #cbd5f5;
}
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
.fx-rate-card {
    padding: 16px;
    height: 100%;
}
.fx-rate-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}
.fx-rate-value {
    font-size: 1.5rem;
    font-weight: 700;
}
.fx-rate-meta {
    color: var(--fx-muted);
    font-size: 0.8rem;
    margin-top: 10px;
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
.fx-form-section {
    padding-bottom: 12px;
    border-bottom: 1px dashed var(--fx-border);
    margin-bottom: 16px;
}
.fx-form-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
}
.fx-form-title {
    font-weight: 700;
    color: var(--fx-ink);
    font-size: 0.95rem;
    margin-bottom: 8px;
}
.fx-form-help {
    color: var(--fx-muted);
    font-size: 0.85rem;
}
.fx-table thead th {
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--fx-muted);
    border-top: none;
}
[data-theme="dark"] .fx-table thead th {
    color: #c7d2fe;
}
.fx-table tbody tr {
    border-bottom: 1px solid var(--fx-border);
}
[data-theme="dark"] .fx-table tbody tr {
    border-bottom: 1px solid rgba(148, 163, 184, 0.12);
}
[data-theme="dark"] .table-striped tbody tr:nth-of-type(odd) {
    background-color: rgba(148, 163, 184, 0.06);
}
.fx-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-weight: 600;
    font-size: 0.78rem;
    padding: 4px 10px;
    border-radius: 999px;
}
.fx-status--active { background: rgba(22, 163, 74, 0.12); color: var(--fx-success); }
.fx-status--paused { background: rgba(245, 158, 11, 0.15); color: var(--fx-warning); }
.fx-status--error { background: rgba(239, 68, 68, 0.12); color: var(--fx-danger); }
.fx-status--triggered { background: rgba(37, 99, 235, 0.12); color: var(--fx-primary); }
.fx-status--neutral { background: rgba(148, 163, 184, 0.16); color: var(--fx-muted); }
[data-theme="dark"] .fx-status--active { background: rgba(34, 197, 94, 0.16); }
[data-theme="dark"] .fx-status--paused { background: rgba(251, 191, 36, 0.18); }
[data-theme="dark"] .fx-status--error { background: rgba(248, 113, 113, 0.18); }
[data-theme="dark"] .fx-status--triggered { background: rgba(96, 165, 250, 0.2); }
[data-theme="dark"] .fx-status--neutral { background: rgba(148, 163, 184, 0.22); }
.fx-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 0.78rem;
    font-weight: 600;
}
.fx-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 999px;
    font-size: 0.85rem;
    background: #0f172a;
    color: #fff;
}
.fx-toast-area.alert,
.fx-toast-area .alert {
    border-radius: 12px;
    box-shadow: var(--fx-shadow-sm);
}
.fx-pricing-card {
    overflow: hidden;
    box-shadow: var(--fx-shadow-lg);
}
.fx-pricing-card .card-header {
    background: linear-gradient(120deg, var(--fx-primary), var(--fx-success));
    color: #fff;
    border-bottom: none;
}
[data-theme="dark"] .fx-pricing-card .card-header {
    background: linear-gradient(120deg, rgba(96, 165, 250, 0.9), rgba(34, 197, 94, 0.9));
}
.fx-plan-card {
    border-radius: 16px;
    border: 1px solid var(--fx-border);
    padding: 16px;
    height: 100%;
    background: var(--fx-surface);
}
.fx-plan-card.highlight {
    background: linear-gradient(130deg, #111827, #1f2937);
    color: #fff;
}
[data-theme="dark"] .fx-plan-card.highlight {
    background: linear-gradient(130deg, #1f2937, #0f172a);
}
.fx-plan-card.highlight .text-muted { color: rgba(226, 232, 240, 0.8) !important; }
.fx-plan-list li { margin-bottom: 8px; }
.fx-plan-note { color: var(--fx-muted); font-size: 0.85rem; }
.source-strip {
    display: flex;
    gap: 12px;
    overflow-x: auto;
    padding: 8px 0;
}
.source-chip {
    min-width: 170px;
    border: 1px solid var(--fx-border);
    border-radius: 12px;
    padding: 10px 12px;
    background: var(--fx-surface);
}
[data-theme="dark"] .source-chip {
    background: rgba(15, 23, 42, 0.9);
}
.source-chip .prices { font-size: 0.9rem; margin: 2px 0; }
.source-chip .label { font-size: 0.8rem; color: var(--fx-muted); }
.btn-check { position: absolute; opacity: 0; }
.btn-channel-email,
.btn-channel-whatsapp { transition: all 0.2s ease; }
.btn-check:checked + .btn-channel-email {
    background-color: var(--fx-primary);
    color: #fff;
    border-color: var(--fx-primary);
}
.btn-check:checked + .btn-channel-whatsapp {
    background-color: #25d366;
    color: #fff;
    border-color: #25d366;
}
.btn-check:disabled + label {
    opacity: 0.65;
    cursor: not-allowed;
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
[data-theme="dark"] .btn-outline-primary.btn-sm,
[data-theme="dark"] .btn-outline-secondary.btn-sm,
[data-theme="dark"] .btn-outline-info.btn-sm,
[data-theme="dark"] .btn-outline-danger.btn-sm {
    border-color: rgba(148, 163, 184, 0.3);
}
[data-theme="dark"] .form-control,
[data-theme="dark"] .input-group-text,
[data-theme="dark"] .custom-select {
    background: rgba(15, 23, 42, 0.95);
    border-color: rgba(148, 163, 184, 0.25);
    color: #e2e8f0;
}
[data-theme="dark"] .input-group-text {
    color: #cbd5f5;
}
[data-theme="dark"] .form-control:focus {
    border-color: #60a5fa;
    box-shadow: 0 0 0 0.15rem rgba(96, 165, 250, 0.25);
}
[data-theme="dark"] .table {
    color: #e2e8f0;
}
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
[data-theme="dark"] .badge-light {
    background: rgba(226, 232, 240, 0.16);
    color: #e2e8f0;
}
.sources-desktop { display: none; }
.sources-mobile { display: block; }
.fx-reveal {
    animation: fx-fade-up 0.6s ease both;
}
.fx-reveal-delay-1 { animation-delay: 0.08s; }
.fx-reveal-delay-2 { animation-delay: 0.16s; }
@keyframes fx-fade-up {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
}
@media (min-width: 768px) {
    .sources-desktop { display: flex; }
    .sources-mobile { display: none; }
}
@media (max-width: 767.98px) {
    .alert-mobile-card .badge { font-size: 0.75rem; }
    .alert-mobile-card .card-body { padding: 0.85rem 1rem; }
    .alert-mobile-card .card-title { font-size: 1rem; }
    .alert-mobile-meta { font-size: 0.9rem; }
    .alert-form-card .card-body { padding: 0.9rem 1rem; }
    .alert-form-card label { font-size: 0.9rem; }
    .page-title { font-size: 1.4rem; }
    .page-subtitle { font-size: 0.95rem; }
}
</style>
<section class="content-header">
    <div class="container-fluid">
        @php
            $utilityId = $utility?->id;
            $subscription = Auth::check()
                ? \App\Models\Subscription::where('user_id', Auth::id())
                    ->where('ends_at', '>=', now())
                    ->when($utilityId, function ($query) use ($utilityId) {
                        $query->whereHas('utilities', function ($u) use ($utilityId) {
                            $u->where('utilities.id', $utilityId);
                        });
                    })
                    ->latest()
                    ->first()
                : null;
            $guestPlan = !Auth::check()
                ? app(\App\Modules\Core\Services\GuestService::class)->getGuestPlan($utilityId)
                : null;
            $plan = $subscription ? $subscription->plan_type : ($guestPlan ?? 'free');
            $frequency = ($plan == 'basic' || $plan == 'pro') ? 'recurring' : 'once';
            $planLabels = ['free' => 'Free', 'basic' => 'Basico', 'pro' => 'Pro'];
            $currentPlanLabel = $planLabels[$plan] ?? ucfirst($plan);
            $globalErrors = collect($errors->all())->filter(fn($m) => str_contains(strtolower($m), 'dashboard'));
            $formatPrice = function ($value) {
                $text = trim((string) $value);
                if ($text === '') {
                    return '';
                }
                $parts = explode('.', $text, 2);
                if (count($parts) === 1) {
                    return $text . '.000';
                }
                return $parts[0] . '.' . str_pad($parts[1], 3, '0');
            };
        @endphp
        <div class="fx-card fx-header fx-reveal">
            <!-- Header principal con acciones rapidas -->
            <div class="fx-header-main">
                <div>
                    <div class="fx-kicker">Alertas inteligentes</div>
                    <h1 class="page-title"><i class="fas fa-money-bill-wave text-success"></i> Alerta de Tipo de Cambio</h1>
                    <p class="page-subtitle mb-0">Monitorea el tipo de cambio y recibe alertas cuando llegue a tu precio ideal.</p>
                </div>
                <div class="fx-header-actions">
                    <a href="#alert-form-wrapper" class="btn btn-primary"><i class="fas fa-bell"></i> Crear alerta</a>
                    <a href="#plans-section" class="btn btn-outline-primary"><i class="fas fa-crown"></i> Ver planes</a>
                    <button type="button" class="btn btn-light fx-theme-toggle" id="theme-toggle" aria-pressed="false">
                        <i class="fas fa-moon"></i> <span class="fx-theme-label">Modo oscuro</span>
                    </button>
                </div>
            </div>
            <div class="row mt-3 g-3">
                <div class="col-md-4">
                    <div class="fx-stat">
                        <i class="fas fa-landmark"></i>
                        <div>
                            <div class="fx-stat-label">Casas de cambio activas</div>
                            <div class="fx-stat-value">{{ $sources->count() }} fuentes</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="fx-stat">
                        <i class="fas fa-bell"></i>
                        <div>
                            <div class="fx-stat-label">Alertas en seguimiento</div>
                            <div class="fx-stat-value">{{ $alerts->count() }} alertas</div>
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
        @if($globalErrors->isNotEmpty())
            <div class="row mt-3">
                <div class="col-12">
                    <div class="alert alert-warning fx-toast-area">
                        <ul class="mb-0">
                            @foreach($globalErrors as $msg)<li>{{ $msg }}</li>@endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif
    </div>
</section>

<section class="content fx-page">
    <div class="container-fluid">
        <!-- Seccion de fuentes con compra/venta -->
        <div class="fx-section">
            <div class="d-flex justify-content-between align-items-end flex-wrap mb-3">
                <div>
                    <h2 class="fx-section-title mb-1">Casas de cambio</h2>
                    <p class="fx-section-subtitle">Compra y venta con ultima actualizacion por fuente.</p>
                </div>
                <span class="fx-chip"><i class="fas fa-bolt"></i> Datos en tiempo real</span>
            </div>
            <div class="row g-3 sources-desktop fx-reveal fx-reveal-delay-1">
                @forelse($sources as $source)
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="fx-card fx-rate-card">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="font-weight-bold">{{ $source->name }}</div>
                            <span class="fx-badge {{ $source->is_active ? 'text-success' : 'text-muted' }}">
                                <i class="fas fa-circle"></i> {{ $source->is_active ? 'Activo' : 'Inactivo' }}
                            </span>
                        </div>
                        @if($source->latestRate)
                            <div class="fx-rate-grid">
                                <div>
                                    <div class="text-muted small">Compra</div>
                                    <div class="fx-rate-value text-primary">{{ number_format($source->latestRate->buy_price, 3) }}</div>
                                </div>
                                <div>
                                    <div class="text-muted small">Venta</div>
                                    <div class="fx-rate-value text-danger">{{ number_format($source->latestRate->sell_price, 3) }}</div>
                                </div>
                            </div>
                            <div class="fx-rate-meta">Ultima actualizacion: {{ $source->latestRate->created_at->diffForHumans() }}</div>
                        @else
                            <div class="fx-empty mt-2">No hay datos recientes.</div>
                        @endif
                        <div class="mt-3">
                            <a href="{{ $source->url }}" target="_blank" class="btn btn-outline-primary btn-sm w-100">
                                <i class="fas fa-external-link-alt"></i> Ver {{ $source->name }}
                            </a>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-12">
                    <div class="fx-empty">No hay fuentes disponibles en este momento.</div>
                </div>
                @endforelse
            </div>
            <div class="sources-mobile fx-reveal fx-reveal-delay-1">
                <div class="source-strip">
                    @forelse($sources as $source)
                    <div class="source-chip">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong style="font-size:0.95rem;">{{ $source->name }}</strong>
                            <span class="fx-badge {{ $source->is_active ? 'text-success' : 'text-muted' }}">
                                <i class="fas fa-circle"></i> {{ $source->is_active ? 'Activo' : 'Inactivo' }}
                            </span>
                        </div>
                        @if($source->latestRate)
                            <div class="prices"><span class="label">Compra:</span> <strong class="text-primary">{{ number_format($source->latestRate->buy_price, 3) }}</strong></div>
                            <div class="prices"><span class="label">Venta:</span> <strong class="text-danger">{{ number_format($source->latestRate->sell_price, 3) }}</strong></div>
                            <div class="fx-rate-meta">Actualizado {{ $source->latestRate->created_at->diffForHumans() }}</div>
                        @else
                            <div class="text-muted" style="font-size:0.85rem;">Sin datos recientes</div>
                        @endif
                        <a href="{{ $source->url }}" target="_blank" class="btn btn-outline-primary btn-sm w-100 mt-2">Ver</a>
                    </div>
                    @empty
                    <div class="fx-empty w-100">No hay fuentes disponibles.</div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="row g-4 align-items-start">
            <div class="col-lg-5 order-1">
                <div class="d-block d-md-none mb-2">
                    <button class="btn btn-outline-primary btn-block" id="toggle-form-btn">
                        <i class="fas fa-sliders-h"></i> Mostrar / ocultar formulario
                    </button>
                </div>
                <div class="card card-primary alert-form-card fx-form-card fx-card fx-reveal fx-reveal-delay-2" id="alert-form-wrapper">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-bell mr-2"></i> <span id="form-title">Crear Nueva Alerta</span></h5>
                    </div>
                    <div class="card-body">
                        @if(session('success')) <div class="alert alert-success fx-toast-area">{{ session('success') }}</div> @endif
                        @php
                            $formErrors = collect($errors->all())->reject(fn($m) => str_contains(strtolower($m), 'dashboard'));
                        @endphp
                        @if($formErrors->isNotEmpty())
                            <div class="alert alert-danger fx-toast-area">
                                <ul class="mb-0">@foreach($formErrors as $error)<li>{{ $error }}</li>@endforeach</ul>
                            </div>
                        @endif
                        <div id="alert-feedback" class="fx-toast-area" role="status" aria-live="polite"></div>

                        <form id="alert-form" action="{{ route('currency-alert.store') }}" method="POST" data-guest-consent="required">
                            @csrf
                            <input type="hidden" id="form-method" name="_method" value="POST">
                            <input type="hidden" id="alert-id" value="">
                            <div class="fx-form-section">
                                <div class="fx-form-title">Paso 1: Fuente y objetivo</div>
                                <div class="form-group">
                                    <label for="exchange_source_id">Casa de cambio</label>
                                    <select name="exchange_source_id" id="exchange_source_id" class="form-control @error('exchange_source_id') is-invalid @enderror" required>
                                        @foreach($sources as $source)<option value="{{ $source->id }}">{{ $source->name }}</option>@endforeach
                                    </select>
                                    <small class="fx-form-help">Selecciona la fuente con la cotizacion que prefieres seguir.</small>
                                    @error('exchange_source_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="form-group">
                                    <label for="target_price">Precio objetivo</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text">S/</span></div>
                                        <input type="number" step="0.001" name="target_price" id="target_price" class="form-control @error('target_price') is-invalid @enderror" placeholder="Ej: 3.750" inputmode="decimal" oninput="if (this.value.includes('.')) { const parts = this.value.split('.'); this.value = parts[0] + '.' + parts[1].slice(0, 3); }" required>
                                    </div>
                                    <small class="fx-form-help">Te avisamos cuando el precio cruce este valor.</small>
                                    @error('target_price')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="fx-form-section">
                                <div class="fx-form-title">Paso 2: Condicion de disparo</div>
                                <div class="form-group">
                                    <label for="condition">Condici&oacute;n</label>
                                    <select name="condition" id="condition" class="form-control @error('condition') is-invalid @enderror" required>
                                        <option value="above">Cuando suba por encima de (Venta)</option>
                                        <option value="below">Cuando baje por debajo de (Compra)</option>
                                    </select>
                                    <small class="fx-form-help">Elige si te interesa cuando el precio sube o baja.</small>
                                    @error('condition')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="fx-form-section">
                                <div class="fx-form-title">Paso 3: Notificacion</div>
                                <div class="form-group">
                                    <label>Canal de notificaci&oacute;n</label>
                                    @php
                                        $canUseWhatsApp = Auth::check()
                                            ? Auth::user()->canUseWhatsApp($utilityId)
                                            : app(\App\Modules\Core\Services\GuestService::class)->canGuestUseWhatsApp($utilityId);
                                        $canUseRecurring = Auth::check()
                                            ? Auth::user()->canUseRecurringAlerts($utilityId)
                                            : app(\App\Modules\Core\Services\GuestService::class)->canGuestUseRecurringAlerts($utilityId);
                                    @endphp
                                    <div class="btn-group d-flex" role="group">
                                        <input type="radio" class="btn-check" name="channel" id="email" value="email" checked>
                                        <label class="btn btn-outline-secondary w-50 btn-channel-email" for="email"><i class="fas fa-envelope"></i> Email</label>
                                        <input type="radio" class="btn-check" name="channel" id="whatsapp" value="whatsapp" {{ !$canUseWhatsApp ? 'disabled' : '' }}>
                                        <label class="btn btn-outline-secondary w-50 btn-channel-whatsapp {{ !$canUseWhatsApp ? 'disabled' : '' }}" for="whatsapp"><i class="fab fa-whatsapp"></i> WhatsApp (Pro)</label>
                                    </div>
                                    @unless($canUseWhatsApp)<small class="fx-form-help d-block mt-1">WhatsApp disponible solo con Plan Pro.</small>@endunless
                                </div>
                                <div class="form-group d-none" id="whatsapp-phone-group">
                                    <label for="contact_phone">Numero de celular (WhatsApp)</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <select class="custom-select" id="whatsapp-country" aria-label="Codigo de pais">
                                                <option value="+51" selected>+51 Peru</option>
                                            </select>
                                        </div>
                                        <input type="tel" name="contact_phone" id="contact_phone" class="form-control" placeholder="999 999 999" inputmode="numeric">
                                    </div>
                                    <small class="fx-form-help">Requerido para WhatsApp.</small>
                                </div>
                                <div class="form-group" id="contact-detail-group">
                                    <label for="contact_detail">Detalle de contacto</label>
                                    <input type="text" name="contact_detail" id="contact_detail" class="form-control @error('contact_detail') is-invalid @enderror" placeholder="tu@email.com" value="{{ Auth::check() ? Auth::user()->email : '' }}">
                                    <small class="fx-form-help">Usaremos este dato para enviarte la alerta.</small>
                                    @error('contact_detail')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="fx-form-section">
                                <div class="fx-form-title">Paso 4: Frecuencia</div>
                                <div class="form-group mb-0">
                                    <label for="frequency">Frecuencia de alerta</label>
                                    @if($canUseRecurring)
                                        <select id="frequency" name="frequency" class="form-control">
                                            <option value="once" {{ $frequency === 'once' ? 'selected' : '' }}>Una vez</option>
                                            <option value="recurring" {{ $frequency === 'recurring' ? 'selected' : '' }}>Recurrente</option>
                                        </select>
                                        <small class="fx-form-help d-block mt-1">Recurrente: se mantiene activa al cumplirse la condición.</small>
                                    @else
                                        <select id="frequency" class="form-control" disabled>
                                            <option value="once" selected>Una vez</option>
                                            <option value="recurring">Recurrente</option>
                                        </select>
                                        <input type="hidden" name="frequency" value="once">
                                        <small class="fx-form-help d-block mt-1">Alertas recurrentes disponibles en planes Basic o Pro.</small>
                                    @endif
                                </div>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary" id="submit-btn"><i class="fas fa-plus-circle"></i> <span id="submit-text">Crear Alerta</span></button>
                                <button type="button" class="btn btn-outline-secondary d-none" id="cancel-edit-btn" onclick="cancelEdit()"><i class="fas fa-times"></i> Cancelar edici&oacute;n</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div id="plans-slot-left"></div>

            </div>

            <div class="col-lg-7 order-2">
                <div class="card fx-card fx-table-card fx-reveal fx-reveal-delay-2" id="alerts-section">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <h5 class="mb-1">Mis Alertas Activas</h5>
                            <small class="text-muted">Ordena visualmente y gestiona tus alertas.</small>
                        </div>
                        <div class="text-muted small"><i class="fas fa-sort"></i> Ordenable</div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive d-none d-md-block">
                            <table class="table mb-0 fx-table">
                                <thead>
                                    <tr>
                                        <th>Fuente <i class="fas fa-sort text-muted"></i></th>
                                        <th>Objetivo</th>
                                        <th>Condici&oacute;n</th>
                                        <th>Frecuencia</th>
                                        <th>Estado</th>
                                        <th>Creado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="alerts-tbody">
                                    @forelse($alerts as $alert)
                                    <tr data-alert-id="{{ $alert->id }}">
                                        <td>{{ $alert->exchangeSource->name }}</td>
                                        <td>S/ {{ $formatPrice($alert->target_price) }}</td>
                                        <td>@if($alert->condition == 'above')<span class="text-success"><i class="fas fa-arrow-up"></i> Suba</span>@else<span class="text-danger"><i class="fas fa-arrow-down"></i> Baje</span>@endif</td>
                                        <td>{{ $alert->frequency === 'recurring' ? 'Recurrente' : 'Una vez' }}</td>
                                        <td>
                                            @if($alert->status == 'active')
                                                <span class="fx-status fx-status--active"><i class="fas fa-check-circle"></i> Activo</span>
                                            @elseif($alert->status == 'paused')
                                                <span class="fx-status fx-status--paused"><i class="fas fa-pause-circle"></i> Pausado</span>
                                            @elseif($alert->status == 'error_email')
                                                <span class="fx-status fx-status--error"><i class="fas fa-exclamation-circle"></i> Error email</span>
                                            @elseif($alert->status == 'triggered')
                                                <span class="fx-status fx-status--triggered"><i class="fas fa-bolt"></i> Disparado</span>
                                            @else
                                                <span class="fx-status fx-status--neutral">{{ $alert->status }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $alert->created_at->format('d/m/Y') }}</td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button type="button" class="btn btn-outline-info" title="Ver detalle"
                                                    onclick="showAlertDetails(this)"
                                                    data-source-name="{{ $alert->exchangeSource->name }}"
                                                    data-price="{{ $alert->target_price }}"
                                                    data-condition="{{ $alert->condition }}"
                                                    data-status="{{ $alert->status }}"
                                                    data-channel="{{ $alert->channel }}"
                                                    data-contact="{{ $alert->contact_detail }}"
                                                    data-contact-phone="{{ $alert->contact_phone }}"
                                                    data-frequency="{{ $alert->frequency }}"
                                                    data-created="{{ $alert->created_at->format('d/m/Y H:i') }}">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-primary" title="Editar" 
                                                    onclick="editAlert(this)"
                                                    data-id="{{ $alert->id }}"
                                                    data-source="{{ $alert->exchange_source_id }}"
                                                    data-price="{{ $alert->target_price }}"
                                                    data-condition="{{ $alert->condition }}"
                                                    data-channel="{{ $alert->channel }}"
                                                    data-contact="{{ $alert->contact_detail }}"
                                                    data-contact-phone="{{ $alert->contact_phone }}"
                                                    data-frequency="{{ $alert->frequency }}">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form action="{{ route('currency-alert.destroy', $alert->id) }}" method="POST" class="d-inline alert-delete-form" data-alert-id="{{ $alert->id }}">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger" title="Eliminar"><i class="fas fa-trash"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr data-empty-row="true"><td colspan="6" class="text-center py-4 text-muted">No tienes alertas activas.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="d-block d-md-none p-3">
                            @forelse($alerts as $alert)
                            <div class="card shadow-sm mb-3 alert-mobile-card fx-card" data-alert-id="{{ $alert->id }}">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="card-title mb-1">{{ $alert->exchangeSource->name }}</h6>
                                            <div class="alert-mobile-meta text-muted">Creado {{ $alert->created_at->format('d/m/Y') }}</div>
                                        </div>
                                        <div>
                                            @if($alert->status == 'active')
                                                <span class="fx-status fx-status--active">Activo</span>
                                            @elseif($alert->status == 'paused')
                                                <span class="fx-status fx-status--paused">Pausado</span>
                                            @elseif($alert->status == 'error_email')
                                                <span class="fx-status fx-status--error">Error email</span>
                                            @elseif($alert->status == 'triggered')
                                                <span class="fx-status fx-status--triggered">Disparado</span>
                                            @else
                                                <span class="fx-status fx-status--neutral">{{ $alert->status }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted">Objetivo</span>
                                            <strong>S/ {{ $formatPrice($alert->target_price) }}</strong>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted">Condici&oacute;n</span>
                                            <span>@if($alert->condition == 'above')<span class="text-success"><i class="fas fa-arrow-up"></i> Suba</span>@else<span class="text-danger"><i class="fas fa-arrow-down"></i> Baje</span>@endif</span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted">Frecuencia</span>
                                            <span>{{ $alert->frequency === 'recurring' ? 'Recurrente' : 'Una vez' }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted">Canal</span>
                                            <span class="text-capitalize">{{ $alert->channel }}</span>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2 mt-3 flex-wrap">
                                        <button type="button" class="btn btn-outline-info btn-sm"
                                            onclick="showAlertDetails(this)"
                                            data-source-name="{{ $alert->exchangeSource->name }}"
                                            data-price="{{ $alert->target_price }}"
                                            data-condition="{{ $alert->condition }}"
                                            data-status="{{ $alert->status }}"
                                            data-channel="{{ $alert->channel }}"
                                            data-contact="{{ $alert->contact_detail }}"
                                            data-contact-phone="{{ $alert->contact_phone }}"
                                            data-frequency="{{ $alert->frequency }}"
                                            data-created="{{ $alert->created_at->format('d/m/Y H:i') }}">
                                            <i class="fas fa-eye"></i> Detalle
                                        </button>
                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                            onclick="editAlert(this)"
                                            data-id="{{ $alert->id }}"
                                            data-source="{{ $alert->exchange_source_id }}"
                                            data-price="{{ $alert->target_price }}"
                                            data-condition="{{ $alert->condition }}"
                                            data-channel="{{ $alert->channel }}"
                                            data-contact="{{ $alert->contact_detail }}"
                                            data-contact-phone="{{ $alert->contact_phone }}"
                                            data-frequency="{{ $alert->frequency }}">
                                            <i class="fas fa-edit"></i> Editar
                                        </button>
                                        <form action="{{ route('currency-alert.destroy', $alert->id) }}" method="POST" class="d-inline alert-delete-form" data-alert-id="{{ $alert->id }}">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="fx-empty">No tienes alertas activas.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div id="plans-slot-right"></div>

            </div>
        </div>

        @if (!empty($utility))
            <div class="row" id="plans-slot-full">
                <div class="col-12" id="plans-slot-full-inner">
                    <div class="fx-section fx-reveal" id="plans-section" data-plans-panel="true">
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
                                'basic' => ['Precio: S/ 1 al mes', '5 alertas al mes', 'Email'],
                                'pro' => ['Precio: S/ 4 al mes', '15 alertas al mes', 'Email + WhatsApp'],
                            ],
                            'planPrices' => ['basic' => 1, 'pro' => 4],
                        ])
                    </div>
                </div>
            </div>
        @endif

        @if(Auth::check())
        <div class="row">
            <div class="col-12">
                <div class="card mt-3 fx-pricing-card" @if(empty($utility)) id="plans-section" @endif>
                    <div class="card-header">
                        <div class="d-flex justify-content-between flex-wrap">
                            <h5 class="mb-0"><i class="fas fa-crown"></i> Planes de Suscripci&oacute;n</h5>
                            <span class="small">Basico S/ 1 - Pro S/ 4</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="fx-plan-card">
                                    <div class="plan-tier">Free</div>
                                    <div class="plan-price">S/ 0</div>
                                    <ul class="list-unstyled fx-plan-list mt-2">
                                        <li><i class="fas fa-check text-success"></i> 1 alerta activa</li>
                                        <li><i class="fas fa-check text-success"></i> Solo Email</li>
                                        <li><i class="fas fa-minus text-muted"></i> Alertas puntuales</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="fx-plan-card">
                                    <div class="plan-tier">Basico</div>
                                    <div class="plan-price">S/ 1 <span class="text-muted" style="font-size:0.9rem;">/ mes</span></div>
                                    <ul class="list-unstyled fx-plan-list mt-2">
                                        <li><i class="fas fa-check text-success"></i> 5 alertas al mes</li>
                                        <li><i class="fas fa-check text-success"></i> Email</li>
                                        <li><i class="fas fa-check text-success"></i> Alertas recurrentes</li>
                                    </ul>
                                    <a href="{{ route('user.subscription.upgrade') }}" class="btn btn-warning w-100">Actualizar a Basico</a>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="fx-plan-card highlight">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <div class="plan-tier"><i class="fas fa-star"></i> Pro</div>
                                        <span class="badge badge-light text-dark">Recomendado</span>
                                    </div>
                                    <div class="plan-price">S/ 4 <span class="text-muted" style="font-size:0.9rem;">/ mes</span></div>
                                    <ul class="list-unstyled fx-plan-list mt-2">
                                        <li><i class="fas fa-check text-success"></i> 15 alertas al mes</li>
                                        <li><i class="fas fa-check text-success"></i> Email + WhatsApp</li>
                                        <li><i class="fas fa-check text-success"></i> Alertas recurrentes</li>
                                    </ul>
                                    <a href="{{ route('user.subscription.upgrade') }}" class="btn btn-light w-100">Actualizar a Pro</a>
                                </div>
                            </div>
                        </div>
                        <div class="fx-plan-note">Precios en soles peruanos. Cancelas cuando quieras.</div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</section>

<div class="fx-comments fx-reveal">
@include('modules.core.partials.comments', ['utility' => $utility, 'comments' => $comments])
</div>

<!-- Modal detalle alerta -->
<div class="modal fade" id="alert-detail-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-eye mr-2"></i> Detalle de alerta</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <ul class="list-unstyled mb-0" id="alert-detail-list"></ul>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const currentUserName = @json(Auth::check() ? Auth::user()->name : null);
    const currentUserEmail = @json(Auth::check() ? Auth::user()->email : null);
    const root = document.documentElement;
    const themeToggle = document.getElementById('theme-toggle');
    const themeStorageKey = 'fx-theme';

    const setTheme = (theme) => {
        if (!root) return;
        if (theme === 'dark') {
            root.setAttribute('data-theme', 'dark');
        } else {
            root.removeAttribute('data-theme');
        }
        if (themeToggle) {
            const isDark = theme === 'dark';
            themeToggle.setAttribute('aria-pressed', isDark ? 'true' : 'false');
            const label = themeToggle.querySelector('.fx-theme-label');
            if (label) label.textContent = isDark ? 'Modo claro' : 'Modo oscuro';
            const icon = themeToggle.querySelector('i');
            if (icon) icon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
        }
    };

    const savedTheme = localStorage.getItem(themeStorageKey);
    if (savedTheme) {
        setTheme(savedTheme);
    }

    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            const isDark = root.getAttribute('data-theme') === 'dark';
            const nextTheme = isDark ? 'light' : 'dark';
            setTheme(nextTheme);
            localStorage.setItem(themeStorageKey, nextTheme);
        });
    }

    const plansPanel = document.querySelector('[data-plans-panel="true"]');
    const plansSlotLeft = document.getElementById('plans-slot-left');
    const plansSlotRight = document.getElementById('plans-slot-right');
    const plansSlotFull = document.getElementById('plans-slot-full');
    const plansSlotFullInner = document.getElementById('plans-slot-full-inner');
    let plansLayoutRaf = null;

    const movePlansPanel = (target) => {
        if (!plansPanel || !target) return;
        if (plansPanel.parentElement !== target) {
            target.appendChild(plansPanel);
        }
    };

    const toggleFullSlot = (visible) => {
        if (!plansSlotFull) return;
        plansSlotFull.style.display = visible ? '' : 'none';
    };

    const applyPlansLayout = () => {
        if (!plansPanel || !alertFormWrapper || !alertsSection) return;
        if (!plansSlotRight || !plansSlotLeft || !plansSlotFull) return;
        const leftHeight = alertFormWrapper.offsetHeight || 0;
        const tableHeight = alertsSection.offsetHeight || 0;
        const panelHeight = plansPanel.offsetHeight || 0;
        if (!leftHeight) {
            movePlansPanel(plansSlotFullInner || plansSlotFull);
            toggleFullSlot(true);
            return;
        }

        if (tableHeight < leftHeight * 0.6) {
            movePlansPanel(plansSlotRight);
            toggleFullSlot(false);
            return;
        }

        if (tableHeight > leftHeight * 1.1) {
            movePlansPanel(plansSlotLeft);
            toggleFullSlot(false);
            return;
        }

        movePlansPanel(plansSlotFullInner || plansSlotFull);
        toggleFullSlot(true);
    };

    const schedulePlansLayout = () => {
        if (!plansPanel) return;
        if (plansLayoutRaf) cancelAnimationFrame(plansLayoutRaf);
        plansLayoutRaf = requestAnimationFrame(applyPlansLayout);
    };

    window.addEventListener('resize', schedulePlansLayout);
    window.addEventListener('load', schedulePlansLayout);

    // --- CRUD de alertas en una sola vista ---
    const alertForm = document.getElementById('alert-form');
    const methodInput = document.getElementById('form-method');
    const alertIdInput = document.getElementById('alert-id');
    const formTitle = document.getElementById('form-title');
    const submitText = document.getElementById('submit-text');
    const cancelBtn = document.getElementById('cancel-edit-btn');
    const defaultAction = "{{ route('currency-alert.store') }}";
    const updateBaseUrl = "{{ url('/currency-alert') }}";
    const defaultFrequency = document.getElementById('frequency')?.value || 'once';
    const contactInput = document.getElementById('contact_detail');
    const alertsTableBody = document.getElementById('alerts-tbody');
    const alertsSection = document.getElementById('alerts-section');
    const alertFeedback = document.getElementById('alert-feedback');
    const whatsappGroup = document.getElementById('whatsapp-phone-group');
    const whatsappCountry = document.getElementById('whatsapp-country');
    const whatsappPhoneInput = document.getElementById('contact_phone');
    const contactDetailGroup = document.getElementById('contact-detail-group');

    const isGuestUser = @json(!Auth::check());
    const storedPhone = isGuestUser && whatsappPhoneInput ? localStorage.getItem('currency_alert_phone') : null;
    const lastPhoneFromServer = @json($lastPhone ?? null);
    const lastEmailFromServer = @json($lastEmail ?? null);
    if (contactInput) {
        if (!isGuestUser && currentUserEmail) {
            contactInput.value = currentUserEmail;
        } else if (lastEmailFromServer) {
            contactInput.value = lastEmailFromServer;
        }
    }
    let defaultPhone = storedPhone || lastPhoneFromServer || '';
    if (whatsappPhoneInput && defaultPhone) {
        fillWhatsappPhone(defaultPhone);
    }
    let defaultContact = contactInput ? contactInput.value : '';

    const rememberContact = (value) => {
        const clean = (value || '').trim();
        if (!clean) return;
        defaultContact = clean;
    };
    const rememberPhone = (value) => {
        const clean = (value || '').trim();
        if (!clean || !isGuestUser) return;
        localStorage.setItem('currency_alert_phone', clean);
    };

      const showAlertFeedback = (message, type = 'success') => {
          if (!alertFeedback || !message) return;
          alertFeedback.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
      };

      const removeAlertFromDom = (alertId, formEl = null) => {
          let removed = false;
          if (formEl) {
              const row = formEl.closest('tr');
              if (row) {
                  row.remove();
                  removed = true;
              }
              const card = formEl.closest('.alert-mobile-card');
              if (card) {
                  card.remove();
                  removed = true;
              }
          }
          if (!removed && alertId) {
              const row = document.querySelector(`tr[data-alert-id="${alertId}"]`);
              if (row) row.remove();
              const card = document.querySelector(`.alert-mobile-card[data-alert-id="${alertId}"]`);
              if (card) card.remove();
          }
          if (alertsTableBody && !alertsTableBody.children.length) {
              const empty = document.createElement('tr');
              empty.dataset.emptyRow = 'true';
              empty.innerHTML = `<td colspan="6" class="text-center py-4 text-muted">No tienes alertas activas.</td>`;
              alertsTableBody.appendChild(empty);
          }
          schedulePlansLayout();
      };

    const getSelectedChannel = () => {
        return document.querySelector('input[name="channel"]:checked')?.value || 'email';
    };

    const setChannel = (channel) => {
        const emailRadio = document.getElementById('email');
        const whatsappRadio = document.getElementById('whatsapp');
        const target = channel || getSelectedChannel();
        if (target === 'whatsapp' && whatsappRadio && !whatsappRadio.disabled) {
            whatsappRadio.checked = true;
        } else if (emailRadio) {
            emailRadio.checked = true;
        }
        const isWhatsapp = (target === 'whatsapp' && whatsappRadio && !whatsappRadio.disabled);
        if (whatsappGroup) {
            whatsappGroup.classList.toggle('d-none', !isWhatsapp);
            whatsappGroup.style.display = isWhatsapp ? 'block' : 'none';
            whatsappGroup.hidden = !isWhatsapp;
        }
        if (whatsappPhoneInput) {
            whatsappPhoneInput.required = isWhatsapp;
            whatsappPhoneInput.disabled = !isWhatsapp;
            if (!isWhatsapp) whatsappPhoneInput.setCustomValidity('');
        }
        if (contactDetailGroup) {
            contactDetailGroup.classList.toggle('d-none', isWhatsapp);
            contactDetailGroup.style.display = isWhatsapp ? 'none' : 'block';
            contactDetailGroup.hidden = isWhatsapp;
        }
        if (contactInput) {
            contactInput.required = !isWhatsapp;
            contactInput.disabled = isWhatsapp;
            if (isWhatsapp) {
                contactInput.setCustomValidity('');
                if (contactInput.value) {
                    contactInput.dataset.lastEmail = contactInput.value;
                }
                contactInput.value = '';
            }
        }
        if (!isWhatsapp && contactInput) {
            const emailValue = (contactInput.value || '').trim();
            if (!emailValue || !emailValue.includes('@')) {
                contactInput.value = contactInput.dataset.lastEmail || currentUserEmail || lastEmailFromServer || '';
            }
        }
    };

    const getWhatsappValue = () => {
        const code = whatsappCountry ? whatsappCountry.value : '+51';
        const raw = whatsappPhoneInput ? whatsappPhoneInput.value : '';
        const phone = (raw || '').replace(/\s+/g, '');
        return phone ? `${code}${phone}` : '';
    };

    const syncContactDetailFromWhatsapp = () => {
        if (!contactInput) return;
        if (document.getElementById('whatsapp')?.checked) {
            contactInput.value = '';
        }
    };

    function fillWhatsappPhone(value) {
        if (!whatsappPhoneInput) return;
        const code = whatsappCountry ? whatsappCountry.value : '+51';
        if (value && value.startsWith(code)) {
            whatsappPhoneInput.value = value.slice(code.length).trim();
        } else {
            whatsappPhoneInput.value = value || '';
        }
    }

    const conditionTemplate = (condition) => {
        return condition === 'above'
            ? '<span class="text-success"><i class="fas fa-arrow-up"></i> Suba</span>'
            : '<span class="text-danger"><i class="fas fa-arrow-down"></i> Baje</span>';
    };

    const statusTemplate = (status) => {
        if (status === 'active') return '<span class="fx-status fx-status--active"><i class="fas fa-check-circle"></i> Activo</span>';
        if (status === 'paused') return '<span class="fx-status fx-status--paused"><i class="fas fa-pause-circle"></i> Pausado</span>';
        if (status === 'error_email') return '<span class="fx-status fx-status--error"><i class="fas fa-exclamation-circle"></i> Error email</span>';
        if (status === 'triggered') return '<span class="fx-status fx-status--triggered"><i class="fas fa-bolt"></i> Disparado</span>';
        return `<span class="fx-status fx-status--neutral">${status || ''}</span>`;
    };

    const escapeHtml = (value) => {
        const div = document.createElement('div');
        div.textContent = value ?? '';
        return div.innerHTML;
    };

    const buildActions = (alert) => {
        const contact = alert.contact_detail || '';
        const contactPhone = alert.contact_phone || '';
        return `
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-info" title="Ver detalle"
                    onclick="showAlertDetails(this)"
                    data-source-name="${escapeHtml(alert.exchange_source_name || '')}"
                    data-price="${alert.target_price}"
                    data-condition="${alert.condition}"
                    data-status="${alert.status}"
                    data-channel="${alert.channel}"
                    data-contact="${escapeHtml(contact)}"
                    data-contact-phone="${escapeHtml(contactPhone)}"
                    data-frequency="${alert.frequency}"
                    data-created="${alert.created_at_formatted || ''}">
                    <i class="fas fa-eye"></i>
                </button>
                <button type="button" class="btn btn-outline-primary" title="Editar" 
                    onclick="editAlert(this)"
                    data-id="${alert.id}"
                    data-source="${alert.exchange_source_id}"
                    data-price="${alert.target_price}"
                    data-condition="${alert.condition}"
                    data-channel="${alert.channel}"
                    data-contact="${contact}"
                    data-contact-phone="${contactPhone}"
                    data-frequency="${alert.frequency}">
                    <i class="fas fa-edit"></i>
                </button>
                  <form action="${updateBaseUrl}/${alert.id}" method="POST" class="d-inline alert-delete-form" data-alert-id="${alert.id}">
                    <input type="hidden" name="_token" value="${csrfToken || ''}">
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-outline-danger" title="Eliminar"><i class="fas fa-trash"></i></button>
                </form>
            </div>
        `;
    };

    const formatPrice = (value) => {
        if (value === null || value === undefined) return '';
        const text = String(value).trim();
        if (!text) return '';
        const parts = text.split('.', 2);
        if (parts.length === 1) return `${text}.000`;
        return `${parts[0]}.${parts[1].padEnd(3, '0')}`;
    };

    const upsertAlertRow = (alert) => {
        if (!alertsTableBody || !alert) return;
        const target = alertsTableBody.querySelector(`tr[data-alert-id="${alert.id}"]`) || document.createElement('tr');
        target.dataset.alertId = alert.id;
        target.innerHTML = `
            <td>${escapeHtml(alert.exchange_source_name || '')}</td>
            <td>S/ ${formatPrice(alert.target_price)}</td>
            <td>${conditionTemplate(alert.condition)}</td>
            <td>${alert.frequency === 'recurring' ? 'Recurrente' : 'Una vez'}</td>
            <td>${statusTemplate(alert.status)}</td>
            <td>${alert.created_at_formatted || ''}</td>
            <td>${buildActions(alert)}</td>
        `;
        if (!target.parentElement) {
            alertsTableBody.prepend(target);
        }
        const emptyRow = alertsTableBody.querySelector('tr[data-empty-row="true"]');
        if (emptyRow) emptyRow.remove();
        schedulePlansLayout();
    };

    window.showAlertDetails = function (btn) {
        const list = document.getElementById('alert-detail-list');
        if (!btn || !list) return;
        const data = btn.dataset || {};
        const items = [
            { label: 'Casa de cambio', value: data.sourceName },
            { label: 'Precio objetivo', value: data.price ? `S/ ${formatPrice(data.price)}` : '' },
            { label: 'Condicion', value: data.condition === 'above' ? 'Suba (Venta)' : 'Baje (Compra)' },
            { label: 'Estado', value: data.status },
            { label: 'Canal', value: data.channel === 'whatsapp' ? 'WhatsApp' : 'Email' },
            { label: 'Contacto', value: data.contact },
            { label: 'Celular', value: data.contactPhone },
            { label: 'Frecuencia', value: data.frequency === 'recurring' ? 'Recurrente' : 'Una vez' },
            { label: 'Creado', value: data.created },
        ];
        list.innerHTML = items.map(item => `<li class="mb-2"><strong>${item.label}:</strong> ${escapeHtml(item.value || '')}</li>`).join('');
        // Bootstrap modal (works if loaded)
        if (typeof $ !== 'undefined' && typeof $('#alert-detail-modal').modal === 'function') {
            $('#alert-detail-modal').modal('show');
        } else {
            const modal = document.getElementById('alert-detail-modal');
            if (!modal) return;
            modal.classList.add('show');
            modal.style.display = 'block';
            modal.removeAttribute('aria-hidden');
            modal.addEventListener('click', (e) => {
                if (e.target === modal || e.target.classList.contains('close')) {
                    modal.classList.remove('show');
                    modal.style.display = 'none';
                    modal.setAttribute('aria-hidden', 'true');
                }
            }, { once: true });
        }
    };

    window.editAlert = function (btn) {
        if (!alertForm || !btn) return;
        const data = btn.dataset || {};
        alertIdInput.value = data.id || '';
        alertForm.action = `${updateBaseUrl}/${data.id}`;
        if (methodInput) methodInput.value = 'PUT';

        document.getElementById('exchange_source_id').value = data.source || '';
        document.getElementById('target_price').value = data.price || '';
        document.getElementById('condition').value = data.condition || 'above';
        setChannel(data.channel);
        if (data.channel === 'whatsapp') {
            fillWhatsappPhone(data.contactPhone || data.contact_phone || '');
        } else if (whatsappPhoneInput) {
            whatsappPhoneInput.value = '';
        }

        if (contactInput) {
            const detail = data.contact || data.contactDetail || '';
            contactInput.value = detail;
        }
        const freqInput = document.getElementById('frequency');
        if (freqInput) freqInput.value = data.frequency || defaultFrequency;

        if (formTitle) formTitle.textContent = 'Editar Alerta';
        if (submitText) submitText.textContent = 'Actualizar Alerta';
        if (cancelBtn) cancelBtn.classList.remove('d-none');
        alertForm.scrollIntoView({ behavior: 'smooth' });
    };

    window.cancelEdit = function () {
        if (!alertForm) return;
        alertForm.reset();
        alertForm.action = defaultAction;
        if (methodInput) methodInput.value = 'POST';
        if (alertIdInput) alertIdInput.value = '';
        setChannel('email');
        if (whatsappPhoneInput && defaultPhone) {
            fillWhatsappPhone(defaultPhone);
        } else if (whatsappPhoneInput) {
            whatsappPhoneInput.value = '';
        }
        if (contactInput) contactInput.value = defaultContact;
        const freqInput = document.getElementById('frequency');
        if (freqInput) freqInput.value = defaultFrequency;
        if (formTitle) formTitle.textContent = 'Crear Nueva Alerta';
        if (submitText) submitText.textContent = 'Crear Alerta';
        if (cancelBtn) cancelBtn.classList.add('d-none');
    };

    if (contactInput) {
        contactInput.addEventListener('change', () => rememberContact(contactInput.value));
    }
    if (whatsappPhoneInput) {
        whatsappPhoneInput.addEventListener('change', () => rememberPhone(getWhatsappValue()));
    }

        if (alertForm) {
            alertForm.addEventListener('submit', async function (e) {
                e.preventDefault();
                const channel = document.getElementById('whatsapp')?.checked ? 'whatsapp' : 'email';
                if (channel === 'whatsapp') {
                    const value = getWhatsappValue();
                    if (!value) {
                        showAlertFeedback('Ingresa un numero de celular para WhatsApp.', 'danger');
                        if (whatsappPhoneInput) whatsappPhoneInput.focus();
                        return;
                    }
                    if (contactInput) contactInput.value = '';
                    rememberPhone(value);
                } else {
                    const emailValue = contactInput ? contactInput.value.trim() : '';
                    if (!emailValue) {
                        showAlertFeedback('Ingresa un email valido.', 'danger');
                        if (contactInput) contactInput.focus();
                        return;
                    }
                }
            const formData = new FormData(alertForm);
            const submitBtn = document.getElementById('submit-btn');
            if (submitBtn) submitBtn.disabled = true;
            try {
                if (typeof showLoader === 'function') showLoader();
                const res = await fetch(alertForm.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || ''
                    },
                    body: formData
                });
                const data = await res.json().catch(() => null);
                if (!res.ok) {
                    const firstError = data?.errors ? Object.values(data.errors)[0]?.[0] : null;
                    const message = firstError || data?.message || 'Ocurrió un error al guardar la alerta.';
                    showAlertFeedback(message, 'danger');
                    return;
                }
                if (data?.alert) {
                    upsertAlertRow(data.alert);
                    showAlertFeedback(data.message || 'Alerta guardada correctamente.', 'success');
                    rememberContact(data.alert.contact_detail);
                    if (data.alert.contact_phone) {
                        defaultPhone = data.alert.contact_phone;
                        rememberPhone(data.alert.contact_phone);
                    }
                    cancelEdit();
                }
            } catch (error) {
                console.error('Error guardando alerta', error);
                showAlertFeedback('No se pudo guardar la alerta. Inténtalo nuevamente.', 'danger');
            } finally {
                if (typeof hideLoader === 'function') hideLoader();
                if (submitBtn) submitBtn.disabled = false;
            }
        });
    }

    const toggleFormBtn = document.getElementById('toggle-form-btn');
    const alertFormWrapper = document.getElementById('alert-form-wrapper');
    if (toggleFormBtn && alertFormWrapper) {
        toggleFormBtn.addEventListener('click', () => {
            alertFormWrapper.classList.toggle('d-none');
            schedulePlansLayout();
        });
    }

    const emailRadio = document.getElementById('email');
    const whatsappRadio = document.getElementById('whatsapp');
    if (emailRadio) emailRadio.addEventListener('change', () => setChannel());
    if (whatsappRadio) whatsappRadio.addEventListener('change', () => setChannel());
    document.addEventListener('click', (event) => {
        const label = event.target.closest('.btn-channel-email, .btn-channel-whatsapp');
        if (!label) return;
        const targetId = label.getAttribute('for');
        const input = targetId ? document.getElementById(targetId) : null;
        if (input && !input.disabled) {
            setChannel(input.value);
        }
    });
    if (whatsappCountry) whatsappCountry.addEventListener('change', syncContactDetailFromWhatsapp);
    if (whatsappPhoneInput) whatsappPhoneInput.addEventListener('input', syncContactDetailFromWhatsapp);
    setChannel();

      // Eliminar alerta sin recargar la pagina
      document.addEventListener('submit', async function (e) {
          const form = e.target;
          if (!form.classList.contains('alert-delete-form')) return;
          e.preventDefault();
          const action = form.getAttribute('action');
          const alertId = form.dataset.alertId;
          if (!action) return;
          if (!confirm('Estas seguro de eliminar esta alerta?')) return;
          const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
          try {
              const res = await fetch(action, {
                  method: 'DELETE',
                  headers: {
                      'Accept': 'application/json',
                      'X-CSRF-TOKEN': token || '',
                  },
              });
if (!res.ok) throw new Error('No se pudo eliminar la alerta.');
              removeAlertFromDom(alertId, form);
              
              // Actualizar contador de alertas en el header
              const alertCounter = document.querySelector('.fx-stat-value');
              if (alertCounter && alertCounter.textContent.includes('alertas')) {
                  const currentCount = parseInt(alertCounter.textContent) || 0;
                  const newCount = Math.max(0, currentCount - 1);
                  alertCounter.textContent = newCount + ' alertas';
              }
              
              showAlertFeedback('Alerta eliminada exitosamente!', 'success');
          } catch (error) {
              console.error('Error eliminando alerta', error);
              showAlertFeedback('No se pudo eliminar la alerta. Intentalo nuevamente.', 'danger');
          }
      });

    /* Comments handled by shared partial: modules.core.partials.comments
    const isGuest = @json(!Auth::check());
    const storageKey = 'guest_comment_name';
    const storedName = isGuest ? localStorage.getItem(storageKey) : null;
    const blocks = document.querySelectorAll('.guest-name-block');

    const applyName = (block, name) => {
        const input = block.querySelector('.guest-name-input');
        const display = block.querySelector('.guest-name-display');
        const label = block.querySelector('.guest-name-label');
        const title = block.querySelector('label');
        if (!input || !display || !label) return;

        if (name && name.trim() !== '') {
            label.textContent = name;
            input.classList.add('d-none');
            input.required = false;
            display.classList.remove('d-none');
            if (title) title.classList.add('d-none');
        } else {
            input.classList.remove('d-none');
            input.required = true;
            display.classList.add('d-none');
            label.textContent = '';
            if (title) title.classList.remove('d-none');
        }
    };

    if (isGuest && storedName) {
        blocks.forEach(block => {
            const input = block.querySelector('.guest-name-input');
            if (input) input.value = storedName;
            applyName(block, storedName);
        });
    }

    document.body.addEventListener('click', function (e) {
        const changeBtn = e.target.closest('.guest-name-change');
        if (!changeBtn) return;

        const block = changeBtn.closest('.guest-name-block');
        if (!block) return;

        localStorage.removeItem(storageKey);
        const input = block.querySelector('.guest-name-input');
        const display = block.querySelector('.guest-name-display');
        if (input) {
            input.classList.remove('d-none');
            input.required = true;
            input.value = '';
            input.focus();
        }
        if (display) display.classList.add('d-none');
        const title = block.querySelector('label');
        if (title) title.classList.remove('d-none');
    });

    const commentStoreUrl = "{{ route('utilities.comments.store', $utility) }}";

    const getInitial = (name) => {
        const clean = (name || '').trim();
        return clean ? clean.charAt(0).toUpperCase() : '?';
    };

    const applyInitials = (scope = document) => {
        scope.querySelectorAll('.comment').forEach(comment => {
            if (comment.dataset.initial) return;
            const strong = comment.querySelector('strong');
            comment.dataset.initial = getInitial(strong ? strong.textContent : '');
        });
        scope.querySelectorAll('.replies > div').forEach(reply => {
            if (reply.dataset.initial) return;
            const strong = reply.querySelector('strong');
            reply.dataset.initial = getInitial(strong ? strong.textContent : '');
        });
    };

    applyInitials();

    const renderReply = (parentEl, comment) => {
        let replies = parentEl.querySelector('.replies');
        if (!replies) {
            replies = document.createElement('div');
            replies.className = 'mt-3 ml-3 replies';
            parentEl.appendChild(replies);
        }
        const replyDiv = document.createElement('div');
        replyDiv.className = 'border-left pl-3 mb-2';
        replyDiv.dataset.commentId = comment.id;
        replyDiv.dataset.initial = getInitial(comment.name);

        replyDiv.innerHTML = `
            <div class="d-flex justify-content-between">
                <strong></strong>
                <small class="text-muted"></small>
            </div>
            <p class="mb-1"></p>
            ${comment.email ? `<small class="text-muted"><i class="fas fa-envelope"></i> ${comment.email}</small>` : ''}
        `;
        replyDiv.querySelector('strong').textContent = comment.name;
        replyDiv.querySelector('small.text-muted').textContent = comment.created_at_human || 'Justo ahora';
        replyDiv.querySelector('p').textContent = comment.comment;
        replies.appendChild(replyDiv);
    };

    const renderComment = (comment) => {
        const list = document.getElementById('comments-list');
        if (!list) return;
        const wrapper = document.createElement('div');
        wrapper.className = 'border-bottom pb-3 mb-3 comment';
        wrapper.dataset.commentId = comment.id;
        wrapper.dataset.initial = getInitial(comment.name);
        const replyTarget = `reply-${comment.id}`;
        const reactUrl = comment.react_url || `/comments/${comment.id}/react`;
        const counts = comment.reactions || {};

        wrapper.innerHTML = `
            <div class="d-flex justify-content-between">
                <strong></strong>
                <small class="text-muted"></small>
            </div>
            <p class="mb-2"></p>
            <div class="d-flex align-items-center flex-wrap mb-2">
                <button class="btn btn-sm btn-outline-primary mr-2 reaction-btn" data-type="like" data-url="${reactUrl}"><i class="far fa-thumbs-up"></i> <span class="reaction-count">${counts.like || 0}</span></button>
                <button class="btn btn-sm btn-outline-secondary mr-2 reaction-btn" data-type="sad" data-url="${reactUrl}"><i class="far fa-frown"></i> <span class="reaction-count">${counts.sad || 0}</span></button>
                <button class="btn btn-sm btn-outline-success mr-2 reaction-btn" data-type="laugh" data-url="${reactUrl}"><i class="far fa-laugh"></i> <span class="reaction-count">${counts.laugh || 0}</span></button>
                <button class="btn btn-sm btn-outline-danger mr-2 reaction-btn" data-type="angry" data-url="${reactUrl}"><i class="far fa-angry"></i> <span class="reaction-count">${counts.angry || 0}</span></button>
                <button class="btn btn-sm btn-link toggle-reply" data-target="#${replyTarget}">Responder</button>
            </div>
            ${comment.email ? `<small class="text-muted d-block mb-2"><i class="fas fa-envelope"></i> ${comment.email}</small>` : ''}
            <div class="collapse" id="${replyTarget}" style="display:none;">
                <form action="${commentStoreUrl}" method="POST" class="mt-2 reply-form" novalidate>
                    <input type="hidden" name="_token" value="${csrfToken || ''}">
                    <input type="hidden" name="parent_id" value="${comment.id}">
                    ${isGuest ? `
                    <div class="form-group guest-name-block">
                        <label>Nombre</label>
                        <input type="text" name="name" class="form-control guest-name-input">
                        <div class="d-flex align-items-center mt-1 guest-name-display d-none">
                            <small class="text-muted mb-0">Comentando como <strong class="guest-name-label"></strong></small>
                            <button type="button" class="btn btn-link btn-sm ml-2 guest-name-change">Cambiar</button>
                        </div>
                    </div>` : `<input type="hidden" name="name" value="${currentUserName || ''}">`}
                    <div class="form-group">
                        <label>Respuesta</label>
                        <textarea name="comment" rows="2" class="form-control" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-reply"></i> Responder</button>
                </form>
            </div>
            <div class="mt-3 ml-3 replies"></div>
        `;

        wrapper.querySelector('strong').textContent = comment.name;
        wrapper.querySelector('small.text-muted').textContent = comment.created_at_human || 'Justo ahora';
        wrapper.querySelector('p').textContent = comment.comment;

        list.appendChild(wrapper);

        // Re-aplicar nombre guardado a nuevos bloques
        if (isGuest && storedName) {
            applyName(wrapper.querySelector('.guest-name-block'), storedName);
        }
    };

    // Infinite scroll comentarios
    const sentinel = document.getElementById('comments-sentinel');
    const loader = document.getElementById('comments-loader');
    let nextCommentsUrl = sentinel ? sentinel.dataset.nextUrl : null;
    const loadingComments = { value: false };

    const loadMoreComments = async () => {
        if (!nextCommentsUrl || loadingComments.value) return;
        loadingComments.value = true;
        try {
            if (loader) loader.classList.remove('d-none');
            const res = await fetch(nextCommentsUrl, { headers: { 'Accept': 'application/json' } });
            if (!res.ok) throw new Error('Error cargando comentarios');
            const data = await res.json();
            if (Array.isArray(data.data)) {
                data.data.forEach(c => {
                    renderComment({
                        id: c.id,
                        parent_id: c.parent_id,
                        name: c.name,
                        email: c.email,
                        comment: c.comment,
                        created_at_human: c.created_at_human,
                        reactions: c.reactions || {},
                    });
                    // Render replies si vienen
                    if (Array.isArray(c.replies)) {
                        const parentEl = document.querySelector(`.comment[data-comment-id="${c.id}"]`);
                        c.replies.forEach(r => parentEl && renderReply(parentEl, r));
                    }
                });
            }
            nextCommentsUrl = data.next_page_url;
            if (!nextCommentsUrl && sentinel) {
                sentinel.textContent = 'No hay más comentarios';
            }
        } catch (e) {
            console.error(e);
        } finally {
            if (loader) loader.classList.add('d-none');
            loadingComments.value = false;
        }
    };

    if (sentinel) {
        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    loadMoreComments();
                }
            });
        });
        observer.observe(sentinel);
    }

    const handleCommentSubmit = async (form) => {
        const nameInput = form.querySelector('.guest-name-input');
        if (nameInput) {
            const isHidden = nameInput.classList.contains('d-none') || nameInput.offsetParent === null || getComputedStyle(nameInput).display === 'none';
            nameInput.required = !isHidden;
            if (isHidden) nameInput.removeAttribute('required');
        }
        const input = form.querySelector('.guest-name-input');
        if (isGuest && input) {
            // Rellenar con nombre almacenado si el input está oculto o vacío
            if ((input.classList.contains('d-none') || input.value.trim() === '') && storedName) {
                input.value = storedName;
            }
            if (input.value.trim() !== '') {
                const name = input.value.trim();
                localStorage.setItem(storageKey, name);
                blocks.forEach(block => applyName(block, name));
            } else {
                // Mostrar campo si no hay nombre para invitados y evitar enviar vacío
                input.classList.remove('d-none');
                input.required = true;
                input.focus();
                return;
            }
        }

        const action = form.getAttribute('action');
        const formData = new FormData(form);
        formData.append('_token', csrfToken || '');
        try {
            const res = await fetch(action, {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                body: formData
            });
            if (!res.ok) throw new Error('Error al enviar comentario');
            const data = await res.json().catch(() => null);
            const parentId = (form.querySelector('input[name="parent_id"]')?.value || '').trim();
            if (data && data.comment) {
                if (parentId) {
                    const parentEl = document.querySelector(`.comment[data-comment-id="${parentId}"]`);
                    if (parentEl) renderReply(parentEl, data.comment);
                    const collapse = form.closest('.collapse');
                    if (collapse) {
                        collapse.classList.remove('show');
                        collapse.style.display = 'none';
                    }
                } else {
                    renderComment(data.comment);
                }
                form.reset();
            } else {
                const textarea = form.querySelector('textarea[name="comment"]');
                if (textarea) textarea.value = '';
            }
        } catch (error) {
            console.error('Error enviando comentario', error);
        }
    };

    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!form.matches('form.comment-form, form.reply-form')) return;
        form.setAttribute('novalidate', 'novalidate');
        e.preventDefault();
        // Asegura que los inputs ocultos no estén marcados como required y los visibles sí
        document.querySelectorAll('.guest-name-input').forEach(inp => {
            const hidden = inp.classList.contains('d-none') || inp.offsetParent === null || getComputedStyle(inp).display === 'none';
            if (hidden) {
                inp.required = false;
                inp.removeAttribute('required');
            } else {
                inp.required = true;
            }
        });
        handleCommentSubmit(form);
    });

    // Ensure hidden guest-name inputs are not marked required by default
    document.querySelectorAll('.guest-name-input').forEach(input => {
        input.required = false;
    });

    // Reactions
    document.addEventListener('click', async function (e) {
        const btn = e.target.closest('.reaction-btn');
        if (!btn) return;
        e.preventDefault();
        const url = btn.dataset.url;
        const type = btn.dataset.type;
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (!url || !type || !token) return;
        btn.disabled = true;
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ type })
            });
            if (!res.ok) throw new Error('Error al reaccionar');
            const data = await res.json().catch(() => null);
            const commentEl = btn.closest('.comment');
            const buttons = commentEl ? commentEl.querySelectorAll('.reaction-btn') : [];
            const countsFromServer = data && data.counts ? data.counts : null;

            buttons.forEach(b => {
                const span = b.querySelector('.reaction-count');
                const bType = b.dataset.type;
                if (!span) return;
                if (countsFromServer) {
                    span.textContent = countsFromServer[bType] || 0;
                } else {
                    span.textContent = (b === btn) ? 1 : 0; // un solo voto por usuario
                }
            });
        } finally {
            btn.disabled = false;
        }
    });

    // Toggle reply forms without depender de Bootstrap collapse
    document.addEventListener('click', function (e) {
        const toggle = e.target.closest('.toggle-reply');
        if (!toggle) return;
        e.preventDefault();
        const target = document.querySelector(toggle.dataset.target);
        if (!target) return;
        target.classList.toggle('show');
        if (target.classList.contains('show')) {
            target.style.display = 'block';
            const input = target.querySelector('.guest-name-input');
            if (input) input.required = true;
        } else {
            target.style.display = 'none';
            const input = target.querySelector('.guest-name-input');
            if (input) input.required = false;
        }
    });
    */
    schedulePlansLayout();
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const emailRadio = document.getElementById('email');
    const whatsappRadio = document.getElementById('whatsapp');
    const phoneGroup = document.getElementById('whatsapp-phone-group');
    const detailGroup = document.getElementById('contact-detail-group');
    const phoneInput = document.getElementById('contact_phone');
    const detailInput = document.getElementById('contact_detail');

    const applyChannelVisibility = () => {
        const isWhatsapp = !!(whatsappRadio && whatsappRadio.checked && !whatsappRadio.disabled);
        if (phoneGroup) {
            phoneGroup.classList.toggle('d-none', !isWhatsapp);
            phoneGroup.hidden = !isWhatsapp;
        }
        if (detailGroup) {
            detailGroup.classList.toggle('d-none', isWhatsapp);
            detailGroup.hidden = isWhatsapp;
        }
        if (phoneInput) {
            phoneInput.required = isWhatsapp;
            phoneInput.disabled = !isWhatsapp;
        }
        if (detailInput) {
            detailInput.required = !isWhatsapp;
            detailInput.disabled = isWhatsapp;
        }
    };

    applyChannelVisibility();
    if (emailRadio) emailRadio.addEventListener('change', applyChannelVisibility);
    if (whatsappRadio) whatsappRadio.addEventListener('change', applyChannelVisibility);
    document.addEventListener('click', (event) => {
        const label = event.target.closest('label[for="email"], label[for="whatsapp"]');
        if (!label) return;
        setTimeout(applyChannelVisibility, 0);
    });
});
</script>

@endsection




