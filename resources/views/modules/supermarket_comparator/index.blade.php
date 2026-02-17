@extends('layouts.public')

@section('title', 'Comparador de supermercados')

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
[data-theme="dark"] body,
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
.smc-form-card .card-header {
    background: linear-gradient(120deg, var(--fx-primary-strong), var(--fx-primary));
    color: #fff;
    border-bottom: none;
}
.smc-store-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 8px;
}
.smc-store-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 10px;
    border-radius: 999px;
    font-size: 0.8rem;
    border: 1px solid var(--fx-border);
}
.smc-selection-summary {
    border: 1px dashed var(--fx-border);
    border-radius: 14px;
    padding: 12px;
    background: var(--fx-surface-soft);
}
[data-theme="dark"] .smc-selection-summary {
    background: rgba(15, 23, 42, 0.8);
}
.smc-selection-summary {
    display: none;
}
.smc-saved-list .list-group-item {
    border: 1px solid var(--fx-border);
    border-radius: 12px;
    margin-bottom: 10px;
}
.smc-results {
    margin-top: 16px;
}
.smc-result-card {
    border-radius: 16px;
    border: 1px solid var(--fx-border);
}
[data-theme="dark"] .btn-outline-primary,
[data-theme="dark"] .btn-outline-secondary,
[data-theme="dark"] .btn-outline-info,
[data-theme="dark"] .btn-outline-danger {
    color: #e2e8f0;
    border-color: rgba(148, 163, 184, 0.35);
}
[data-theme="dark"] .btn-primary {
    background: #3b82f6;
    border-color: #3b82f6;
    box-shadow: var(--fx-glow);
}
[data-theme="dark"] .form-control,
[data-theme="dark"] .input-group-text,
[data-theme="dark"] .custom-select {
    background: rgba(15, 23, 42, 0.95);
    border-color: rgba(148, 163, 184, 0.25);
    color: #e2e8f0;
}
[data-theme="dark"] .table { color: #e2e8f0; }
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
.smc-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.5);
    z-index: 1050;
}
.smc-modal {
    position: fixed;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    z-index: 1060;
}
.smc-modal-card {
    background: var(--fx-surface);
    border: 1px solid var(--fx-border);
    border-radius: 16px;
    box-shadow: var(--fx-shadow-lg);
    width: min(560px, 100%);
    padding: 20px;
}
</style>

@php
    $utilityId = $utility?->id;
    $plan = Auth::check()
        ? (Auth::user()?->getActivePlan($utilityId) ?? 'free')
        : app(\App\Modules\Core\Services\GuestService::class)->getGuestPlan($utilityId);
    $planLabels = ['free' => 'Free', 'basic' => 'Basico', 'pro' => 'Pro'];
    $currentPlanLabel = $planLabels[$plan] ?? ucfirst($plan);
    $storeLabels = [
        'plaza_vea' => 'Plaza Vea',
        'tottus' => 'Tottus',
        'metro' => 'Metro',
        'wong' => 'Wong',
    ];
    $selectedStores = old('stores', $selectedStores ?? []);
    $selectedStores = is_array($selectedStores) ? array_map('strtolower', $selectedStores) : [];
@endphp

<section class="content-header">
    <div class="container-fluid">
        <div class="fx-card fx-header fx-reveal">
            <div class="fx-header-main">
                <div>
                    <div class="fx-kicker">Comparador inteligente</div>
                    <h1 class="page-title"><i class="fas fa-shopping-basket text-primary"></i> Comparador de supermercados</h1>
                    <p class="page-subtitle mb-0">Encuentra el mejor precio entre tiendas con un solo analisis.</p>
                </div>
                <div class="fx-header-actions">
                    <a href="#smc-form" class="btn btn-primary"><i class="fas fa-search"></i> Comparar ahora</a>
                    <a href="#smc-plans" class="btn btn-outline-primary"><i class="fas fa-crown"></i> Ver planes</a>
                    <button type="button" class="btn btn-light fx-theme-toggle" id="theme-toggle" aria-pressed="false">
                        <i class="fas fa-moon"></i> <span class="fx-theme-label">Modo oscuro</span>
                    </button>
                </div>
            </div>
            <div class="row mt-3 g-3">
                <div class="col-md-4">
                    <div class="fx-stat">
                        <i class="fas fa-store"></i>
                        <div>
                            <div class="fx-stat-label">Tiendas activas</div>
                            <div class="fx-stat-value">{{ count($storeLabels) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="fx-stat">
                        <i class="fas fa-save"></i>
                        <div>
                            <div class="fx-stat-label">Compras guardadas</div>
                            <div class="fx-stat-value">{{ $savedPurchases->count() }}</div>
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
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">X</button>
                        <h5 class="mb-2"><i class="icon fas fa-ban"></i> Revisa el formulario</h5>
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (session('status'))
                    <div class="alert alert-success alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">X</button>
                        <i class="icon fas fa-check"></i> {{ session('status') }}
                    </div>
                @endif

                <div class="card fx-card smc-form-card" id="smc-form">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-search mr-1"></i> Buscar productos</h3>
                    </div>
                    <div class="card-body">
                        <form id="smc-search-form" action="{{ route('supermarket-comparator.search') }}" method="POST" data-async="true" data-guest-consent="required">
                            @csrf
                            @if (!empty($editingPurchase))
                                <input type="hidden" name="purchase_uuid" value="{{ $editingPurchase->uuid }}">
                            @endif

                            <div class="form-group">
                                <label>Productos (uno por linea)</label>
                                <textarea name="queries" rows="4" class="form-control" placeholder="Ej: Leche Gloria 1L" required>{{ old('queries', $query ?? '') }}</textarea>
                                <small class="text-muted">Se procesan en bloques de 10 por solicitud.</small>
                            </div>

                            <div class="form-group">
                                <label>Supermercados</label>
                                <div class="smc-store-grid">
                                    @foreach ($storeLabels as $code => $label)
                                        @php
                                            $isTottus = $code === 'tottus';
                                            $disabled = $isTottus && $plan !== 'pro';
                                            $checked = in_array($code, $selectedStores, true) && !$disabled;
                                            $inputId = 'store_' . $code;
                                        @endphp
                                        <div class="custom-control custom-checkbox">
                                            <input class="custom-control-input" type="checkbox" id="{{ $inputId }}" name="stores[]" value="{{ $code }}" {{ $checked ? 'checked' : '' }} {{ $disabled ? 'disabled' : '' }}>
                                            <label class="custom-control-label" for="{{ $inputId }}">
                                                {{ $label }} @if ($disabled) <small class="text-muted">(Pro)</small> @endif
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="smc-store-error text-danger small mt-2 d-none">Selecciona al menos un supermercado.</div>
                                <small class="text-muted d-block mt-2">Free: 2 tiendas, Basic: 3 tiendas, Pro: todas + Tottus.</small>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-balance-scale mr-1"></i> Comparar precios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card fx-card mb-3" id="smc-global-selection">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-list-check mr-1"></i> Seleccion global</h3>
                    </div>
                    <div class="card-body">
                        <div class="smc-global-empty text-muted">Selecciona productos para ver el resumen global.</div>
                        <div class="smc-global-content d-none">
                            <div class="d-flex align-items-center justify-content-between flex-wrap">
                                <div class="font-weight-bold">Seleccionados: <span class="smc-global-count">0</span></div>
                                <div class="text-muted small">
                                    Total normal: S/ <span class="smc-global-total-normal">0.00</span> ·
                                    Total tarjeta: S/ <span class="smc-global-total-card">0.00</span>
                                </div>
                            </div>
                            <div class="table-responsive mt-2">
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr class="text-muted">
                                            <th>Producto</th>
                                            <th>Tienda</th>
                                            <th class="text-right">Cantidad</th>
                                            <th class="text-right">Precio</th>
                                            <th class="text-right">Tarjeta</th>
                                            <th class="text-right">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody class="smc-global-items-body"></tbody>
                                </table>
                            </div>
                            <small class="text-muted d-block mt-2">Totales por tienda (normal y tarjeta).</small>
                            <div class="table-responsive mt-2">
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr class="text-muted">
                                            <th>Tienda</th>
                                            <th class="text-right">Total normal</th>
                                            <th class="text-right">Total tarjeta</th>
                                        </tr>
                                    </thead>
                                    <tbody class="smc-global-store-totals-body"></tbody>
                                </table>
                            </div>
                            @if (empty($editingPurchase))
                                <div class="mt-3">
                                    <div class="d-flex align-items-center flex-wrap">
                                        <input class="form-control form-control-sm mr-2 smc-purchase-name" type="text" maxlength="120" placeholder="Nombre de la compra (opcional)">
                                        <button class="btn btn-sm btn-outline-primary smc-save-purchase" type="button">
                                            <i class="fas fa-save mr-1"></i> Guardar compra
                                        </button>
                                    </div>
                                </div>
                            @endif
                            <div class="mt-2">
                                <button class="btn btn-sm btn-success smc-fill-cart" type="button">
                                    <i class="fas fa-shopping-cart mr-1"></i> Llenar carrito
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="smc-fill-cart-backdrop" class="smc-modal-backdrop d-none"></div>
                <div id="smc-fill-cart-modal" class="smc-modal d-none" role="dialog" aria-modal="true" aria-labelledby="smc-fill-cart-title">
                    <div class="smc-modal-card">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h5 class="mb-0" id="smc-fill-cart-title">Llenar carrito</h5>
                            <button class="btn btn-sm btn-outline-secondary smc-fill-cart-close" type="button">Cerrar</button>
                        </div>
                        <div class="form-group">
                            <label for="smc-fill-cart-store">Tienda</label>
                            <select id="smc-fill-cart-store" class="form-control form-control-sm"></select>
                            <small class="text-muted d-block mt-1">Por ahora solo Plaza Vea.</small>
                        </div>
                        <div class="form-group">
                            <label for="smc-agent-device-id">Device ID del agente local</label>
                            <input id="smc-agent-device-id" class="form-control form-control-sm" type="text" maxlength="120" placeholder="Pega aquí el device_id del SMC Agent">
                            <small class="text-muted d-block mt-1">Encuéntralo en el archivo config.json del SMC Agent.</small>
                        </div>
                        <div class="smc-fill-cart-summary text-muted small"></div>
                        <div class="smc-fill-cart-feedback alert d-none mt-3" role="alert"></div>
                        <div class="d-flex justify-content-end mt-3">
                            <button class="btn btn-sm btn-outline-secondary smc-fill-cart-cancel" type="button">Cancelar</button>
                            <button class="btn btn-sm btn-primary ml-2 smc-fill-cart-run" type="button">
                                <i class="fas fa-play mr-1"></i> Iniciar
                            </button>
                        </div>
                    </div>
                </div>

                <div id="smc-results" class="smc-results">
                    @if (($phase ?? 'start') === 'start')
                        <div class="fx-empty">Ingresa productos y elige tiendas para empezar a comparar.</div>
                    @else
                        @include('modules.supermarket_comparator.partials.results', [
                            'phase' => $phase ?? 'start',
                            'contextToken' => $contextToken ?? null,
                            'result' => $result ?? null,
                            'results' => $results ?? [],
                            'editingPurchase' => $editingPurchase ?? null,
                        ])
                    @endif
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card fx-card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-store mr-1"></i> Tiendas disponibles</h3>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-wrap">
                            @foreach ($storeLabels as $code => $label)
                                @php $isTottus = $code === 'tottus'; @endphp
                                <span class="smc-store-pill mr-2 mb-2 {{ $isTottus && $plan !== 'pro' ? 'text-muted' : '' }}">
                                    <i class="fas fa-circle {{ $isTottus && $plan !== 'pro' ? 'text-muted' : 'text-success' }}"></i>
                                    {{ $label }}
                                </span>
                            @endforeach
                        </div>
                        <small class="text-muted">Tottus solo en plan Pro.</small>
                    </div>
                </div>

                <div class="card fx-card mt-3">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-save mr-1"></i> Compras guardadas</h3>
                    </div>
                    <div class="card-body smc-saved-list" id="smc-saved-purchases">
                        @forelse ($savedPurchases as $purchase)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="font-weight-bold">{{ $purchase->label }}</div>
                                        <small class="text-muted">Items: {{ $purchase->items_count ?? 0 }}</small>
                                    </div>
                                    <span class="badge badge-light">{{ $purchase->created_at->format('d/m/Y') }}</span>
                                </div>
                                <div class="mt-2 d-flex flex-wrap">
                                    <a href="{{ route('supermarket-comparator.purchases.show', $purchase->uuid) }}" class="btn btn-sm btn-outline-primary mr-2 mb-2">
                                        <i class="fas fa-eye mr-1"></i> Ver
                                    </a>
                                    <a href="{{ route('supermarket-comparator.purchases.run', $purchase->uuid) }}" class="btn btn-sm btn-outline-success mr-2 mb-2">
                                        <i class="fas fa-play mr-1"></i> Comparar
                                    </a>
                                    <a href="{{ route('supermarket-comparator.purchases.edit', $purchase->uuid) }}" class="btn btn-sm btn-outline-secondary mr-2 mb-2">
                                        <i class="fas fa-edit mr-1"></i> Editar
                                    </a>
                                    <form action="{{ route('supermarket-comparator.purchases.delete', $purchase->uuid) }}" method="POST" class="d-inline mb-2" onsubmit="return confirm('Eliminar esta compra?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">
                                            <i class="fas fa-trash mr-1"></i> Eliminar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="text-muted">No tienes compras guardadas aun.</div>
                        @endforelse
                    </div>
                </div>

                @if (!empty($utility))
                    <div class="fx-section fx-reveal mt-3" id="smc-plans">
                        <div class="d-flex justify-content-between align-items-end flex-wrap mb-2">
                            <div>
                                <h2 class="fx-section-title mb-1"><i class="fas fa-credit-card"></i> Planes y pagos</h2>
                                <p class="fx-section-subtitle">Aumenta limites y compara mas tiendas.</p>
                            </div>
                            <span class="fx-chip"><i class="fas fa-shield-alt"></i> Pago seguro</span>
                        </div>
                        @include('modules.core.partials.mercadopago_plans', [
                            'utility' => $utility,
                            'planDetails' => [
                                'basic' => [
                                    'Hasta 20 productos por dia',
                                    'Hasta 3 tiendas',
                                    'Comparacion rapida',
                                ],
                                'pro' => [
                                    'Hasta 50 productos por dia',
                                    'Todas las tiendas',
                                    'Incluye Tottus',
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
    const resultsWrap = document.getElementById('smc-results');
    const searchForm = document.getElementById('smc-search-form');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

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

    const globalSummary = document.getElementById('smc-global-selection');
    const globalEmpty = globalSummary ? globalSummary.querySelector('.smc-global-empty') : null;
    const globalContent = globalSummary ? globalSummary.querySelector('.smc-global-content') : null;
    const globalCount = globalSummary ? globalSummary.querySelector('.smc-global-count') : null;
    const globalTotalNormal = globalSummary ? globalSummary.querySelector('.smc-global-total-normal') : null;
    const globalTotalCard = globalSummary ? globalSummary.querySelector('.smc-global-total-card') : null;
    const globalItemsBody = globalSummary ? globalSummary.querySelector('.smc-global-items-body') : null;
    const globalStoreTotalsBody = globalSummary ? globalSummary.querySelector('.smc-global-store-totals-body') : null;
    const fillCartModal = document.getElementById('smc-fill-cart-modal');
    const fillCartBackdrop = document.getElementById('smc-fill-cart-backdrop');
    const fillCartStoreSelect = document.getElementById('smc-fill-cart-store');
    const fillCartDeviceInput = document.getElementById('smc-agent-device-id');
    const fillCartSummary = document.querySelector('.smc-fill-cart-summary');
    const fillCartFeedback = document.querySelector('.smc-fill-cart-feedback');
    const fillCartDeviceStorageKey = 'smc-agent-device-id';
    let fillCartPollTimer = null;
    let fillCartItems = [];

    const updateGlobalSelectionSummary = () => {
        if (!globalSummary || !resultsWrap) return;
        const selections = Array.from(resultsWrap.querySelectorAll('.smc-select-item:checked'));
        if (globalItemsBody) globalItemsBody.innerHTML = '';
        if (globalStoreTotalsBody) globalStoreTotalsBody.innerHTML = '';

        if (!selections.length) {
            if (globalEmpty) globalEmpty.classList.remove('d-none');
            if (globalContent) globalContent.classList.add('d-none');
            if (globalCount) globalCount.textContent = '0';
            if (globalTotalNormal) globalTotalNormal.textContent = '0.00';
            if (globalTotalCard) globalTotalCard.textContent = '0.00';
            return;
        }

        if (globalEmpty) globalEmpty.classList.add('d-none');
        if (globalContent) globalContent.classList.remove('d-none');

        let totalNormal = 0;
        let totalCard = 0;
        const storeTotals = {};

        selections.forEach((checkbox) => {
            const row = checkbox.closest('tr');
            const qtyInput = row ? row.querySelector('.smc-qty') : null;
            const unitSelect = row ? row.querySelector('.smc-unit') : null;
            const qty = parseFloat(qtyInput?.value || '1') || 1;
            const unit = unitSelect?.value || 'un';
            const price = parseFloat(checkbox.dataset.price || '0') || 0;
            const cardPrice = parseFloat(checkbox.dataset.cardPrice || '0') || 0;
            const effectiveCard = cardPrice > 0 ? cardPrice : price;
            const storeLabel = (checkbox.dataset.storeLabel || checkbox.dataset.store || 'OTROS').toString();
            const storeKey = storeLabel.toUpperCase();
            const title = (checkbox.dataset.title || '').toString();
            const lineNormal = price * qty;
            const lineCard = effectiveCard * qty;

            totalNormal += lineNormal;
            totalCard += lineCard;
            if (!storeTotals[storeKey]) storeTotals[storeKey] = { normal: 0, card: 0 };
            storeTotals[storeKey].normal += lineNormal;
            storeTotals[storeKey].card += lineCard;

            if (globalItemsBody) {
                const itemRow = document.createElement('tr');
                itemRow.innerHTML = `
                    <td>${title || '-'}</td>
                    <td class="text-uppercase">${storeKey}</td>
                    <td class="text-right">${qty} ${unit}</td>
                    <td class="text-right">S/ ${price.toFixed(2)}</td>
                    <td class="text-right">${cardPrice > 0 ? `S/ ${cardPrice.toFixed(2)}` : '-'}</td>
                    <td class="text-right">S/ ${lineCard.toFixed(2)}</td>
                `;
                globalItemsBody.appendChild(itemRow);
            }
        });

        if (globalCount) globalCount.textContent = selections.length.toString();
        if (globalTotalNormal) globalTotalNormal.textContent = totalNormal.toFixed(2);
        if (globalTotalCard) globalTotalCard.textContent = totalCard.toFixed(2);

        if (globalStoreTotalsBody) {
            Object.keys(storeTotals).forEach((store) => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${store}</td>
                    <td class="text-right">S/ ${storeTotals[store].normal.toFixed(2)}</td>
                    <td class="text-right">S/ ${storeTotals[store].card.toFixed(2)}</td>
                `;
                globalStoreTotalsBody.appendChild(row);
            });
            if (!Object.keys(storeTotals).length) {
                const emptyRow = document.createElement('tr');
                emptyRow.innerHTML = '<td colspan="3" class="text-muted">Sin seleccion</td>';
                globalStoreTotalsBody.appendChild(emptyRow);
            }
        }
    };

    const refreshSelections = () => {
        updateGlobalSelectionSummary();
    };

    const fetchForm = async (form, target) => {
        const action = form.getAttribute('action');
        if (!action) return;
        const formData = new FormData(form);
        try {
            if (typeof showLoader === 'function') showLoader();
            const res = await fetch(action, {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                body: formData,
            });
            if (!res.ok) throw new Error('Error en la solicitud');
            const data = await res.json();
            if (data && data.html && target) {
                target.innerHTML = data.html;
                refreshSelections();
            }
        } catch (error) {
            console.error(error);
            form.submit();
        } finally {
            if (typeof hideLoader === 'function') hideLoader();
        }
    };

    const parseQueryLines = (raw) => {
        if (!raw) return [];
        const normalized = raw.replace(/\r\n/g, '\n').replace(/\r/g, '\n');
        return normalized.split('\n').map((line) => line.trim()).filter((line) => line !== '');
    };

    const chunkLines = (lines, size) => {
        const chunks = [];
        for (let i = 0; i < lines.length; i += size) {
            chunks.push(lines.slice(i, i + size));
        }
        return chunks;
    };

    const appendHtml = (target, html, beforeNode) => {
        const temp = document.createElement('div');
        temp.innerHTML = html;
        while (temp.firstChild) {
            if (beforeNode && beforeNode.parentNode === target) {
                target.insertBefore(temp.firstChild, beforeNode);
            } else {
                target.appendChild(temp.firstChild);
            }
        }
    };

    const submitInBatches = async (form, target) => {
        const action = form.getAttribute('action');
        if (!action) return;
        if (form.dataset.smcBusy === 'true') return;
        form.dataset.smcBusy = 'true';

        const textarea = form.querySelector('textarea[name="queries"]');
        const lines = parseQueryLines(textarea ? textarea.value : '');
        if (!lines.length) {
            form.dataset.smcBusy = 'false';
            form.submit();
            return;
        }

        const chunks = chunkLines(lines, 10);
        const submitBtn = form.querySelector('button[type="submit"]');
        const submitLabel = submitBtn ? submitBtn.innerHTML : '';
        if (submitBtn) submitBtn.disabled = true;

        target.innerHTML = '';
        const progress = document.createElement('div');
        progress.className = 'fx-empty';
        progress.textContent = `Procesando 0/${chunks.length} bloques...`;
        target.appendChild(progress);

        try {
            if (typeof showLoader === 'function') showLoader();

            for (let i = 0; i < chunks.length; i++) {
                const formData = new FormData(form);
                formData.set('queries', chunks[i].join('\n'));

                const res = await fetch(action, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: formData,
                });

                if (!res.ok) {
                    let message = 'Error en la solicitud';
                    try {
                        const payload = await res.json();
                        if (payload && payload.message) message = payload.message;
                    } catch (e) {
                        // ignore json parse errors
                    }
                    throw new Error(message);
                }

                const data = await res.json();
                if (data && data.html) {
                    appendHtml(target, data.html, progress);
                    refreshSelections();
                }
                progress.textContent = `Procesando ${i + 1}/${chunks.length} bloques...`;
            }

            progress.textContent = 'Listo.';
            setTimeout(() => {
                if (progress.parentNode) progress.parentNode.removeChild(progress);
            }, 1200);
        } catch (error) {
            console.error(error);
            alert(error.message || 'No se pudo completar el procesamiento por bloques.');
        } finally {
            if (typeof hideLoader === 'function') hideLoader();
            if (submitBtn) submitBtn.disabled = false;
            if (submitBtn && submitLabel) submitBtn.innerHTML = submitLabel;
            form.dataset.smcBusy = 'false';
        }
    };

    if (searchForm && resultsWrap) {
        searchForm.addEventListener('submit', function (e) {
            if (searchForm.dataset.async !== 'true') return;
            const storeError = searchForm.querySelector('.smc-store-error');
            const selectedStores = searchForm.querySelectorAll('input[name="stores[]"]:checked');
            if (!selectedStores.length) {
                e.preventDefault();
                if (storeError) storeError.classList.remove('d-none');
                alert('Selecciona al menos un supermercado.');
                return;
            }
            if (storeError) storeError.classList.add('d-none');
            e.preventDefault();
            submitInBatches(searchForm, resultsWrap);
        });
    }

    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!form.classList.contains('smc-compare-form')) return;
        e.preventDefault();
        const card = form.closest('.smc-result-card');
        const target = card ? card.querySelector('.smc-compare-target') : resultsWrap;
        fetchForm(form, target);
    });

    const retrySearch = async (button) => {
        if (button.dataset.smcBusy === 'true') return;
        const query = (button.dataset.query || '').trim();
        if (!query) return;
        const card = button.closest('.smc-result-card');
        const contextToken = (button.dataset.contextToken || (card ? card.dataset.contextToken : '') || '').toString();
        const storeList = (button.dataset.stores || '').split(',').map((s) => s.trim()).filter((s) => s !== '');
        const stores = storeList.length
            ? storeList
            : Array.from(document.querySelectorAll('input[name="stores[]"]:checked')).map((el) => el.value);
        const purchaseUuid = document.querySelector('input[name="purchase_uuid"]')?.value || '';
        const formData = new FormData();
        formData.append('query', query);
        stores.forEach((store) => formData.append('stores[]', store));
        if (contextToken) formData.append('context_token', contextToken);
        if (purchaseUuid) formData.append('purchase_uuid', purchaseUuid);

        try {
            button.dataset.smcBusy = 'true';
            button.disabled = true;
            if (typeof showLoader === 'function') showLoader();
            const res = await fetch("{{ url('/supermarket-comparator/retry-search') }}", {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: formData,
            });
            if (!res.ok) {
                let message = 'Error al reintentar';
                try {
                    const payload = await res.json();
                    if (payload && payload.message) message = payload.message;
                } catch (e) {
                    // ignore json parse errors
                }
                throw new Error(message);
            }
            const data = await res.json();
            if (data && data.html && card) {
                card.outerHTML = data.html;
            }
        } catch (error) {
            console.error(error);
            alert('No se pudo reintentar el scrapeo. Intenta nuevamente.');
        } finally {
            button.disabled = false;
            button.dataset.smcBusy = 'false';
            if (typeof hideLoader === 'function') hideLoader();
        }
    };
    window.smcRetrySearch = retrySearch;

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.smc-retry-search');
        if (!btn) return;
        e.preventDefault();
        retrySearch(btn);
    });

    const collectSelectedItems = (scope) => {
        const root = scope || resultsWrap || document;
        const items = [];
        root.querySelectorAll('.smc-select-item:checked').forEach((checkbox) => {
            const row = checkbox.closest('tr');
            const qtyInput = row ? row.querySelector('.smc-qty') : null;
            const unitSelect = row ? row.querySelector('.smc-unit') : null;
            items.push({
                store: checkbox.dataset.store || null,
                store_label: checkbox.dataset.storeLabel || checkbox.dataset.store || null,
                title: checkbox.dataset.title || '',
                url: checkbox.dataset.url || null,
                image_url: checkbox.dataset.image || null,
                quantity: parseFloat(qtyInput?.value || '1') || 1,
                unit: unitSelect?.value || 'un',
                price: parseFloat(checkbox.dataset.price || '0') || 0,
                card_price: parseFloat(checkbox.dataset.cardPrice || '0') || null,
            });
        });
        return items;
    };

    const openFillCartModal = (items) => {
        if (!fillCartModal || !fillCartBackdrop || !fillCartStoreSelect) return;
        fillCartItems = items || [];
        if (fillCartPollTimer) {
            clearTimeout(fillCartPollTimer);
            fillCartPollTimer = null;
        }
        const storeCounts = {};
        fillCartItems.forEach((item) => {
            const code = (item.store || '').toString().toLowerCase();
            if (!code) return;
            storeCounts[code] = (storeCounts[code] || 0) + 1;
        });

        const allowedStores = Object.keys(storeCounts).filter((s) => s === 'plaza_vea');
        fillCartStoreSelect.innerHTML = '';
        allowedStores.forEach((store) => {
            const opt = document.createElement('option');
            opt.value = store;
            opt.textContent = store === 'plaza_vea' ? 'Plaza Vea' : store;
            fillCartStoreSelect.appendChild(opt);
        });

        const total = fillCartItems.length;
        const plazaCount = storeCounts.plaza_vea || 0;
        if (fillCartSummary) {
            fillCartSummary.textContent = `Seleccionados: ${total}. Plaza Vea: ${plazaCount}.`;
        }
        if (fillCartFeedback) {
            fillCartFeedback.classList.add('d-none');
            fillCartFeedback.textContent = '';
        }
        if (fillCartDeviceInput) {
            const savedDeviceId = localStorage.getItem(fillCartDeviceStorageKey) || '';
            fillCartDeviceInput.value = savedDeviceId;
        }

        const runButton = fillCartModal.querySelector('.smc-fill-cart-run');
        if (runButton) {
            runButton.disabled = allowedStores.length === 0;
        }
        fillCartBackdrop.classList.remove('d-none');
        fillCartModal.classList.remove('d-none');
    };

    const closeFillCartModal = () => {
        if (!fillCartModal || !fillCartBackdrop) return;
        if (fillCartPollTimer) {
            clearTimeout(fillCartPollTimer);
            fillCartPollTimer = null;
        }
        fillCartBackdrop.classList.add('d-none');
        fillCartModal.classList.add('d-none');
    };

    const setFillCartFeedback = (message, isError) => {
        if (!fillCartFeedback) return;
        fillCartFeedback.classList.remove('d-none', 'alert-success', 'alert-danger');
        fillCartFeedback.classList.add(isError ? 'alert-danger' : 'alert-success');
        fillCartFeedback.textContent = message;
    };

    const parseResponseJson = async (res) => {
        const text = await res.text();
        if (!text) return null;
        try {
            return JSON.parse(text);
        } catch (e) {
            return { raw: text };
        }
    };

    const applyComparisonSort = (comparisonBlock, order) => {
        if (!comparisonBlock) return;
        const tables = comparisonBlock.querySelectorAll('.smc-sortable-table');
        tables.forEach((table) => {
            const body = table.querySelector('tbody');
            if (!body) return;
            const rows = Array.from(body.querySelectorAll('tr'));
            rows.sort((a, b) => {
                const aPrice = parseFloat(a.dataset.sortPrice || '0') || 0;
                const bPrice = parseFloat(b.dataset.sortPrice || '0') || 0;
                return order === 'cheap' ? (aPrice - bPrice) : (bPrice - aPrice);
            });
            rows.forEach((row) => body.appendChild(row));
        });
    };

    const setSortButtonState = (comparisonBlock, activeOrder) => {
        if (!comparisonBlock) return;
        comparisonBlock.querySelectorAll('.smc-sort-action').forEach((btn) => {
            const order = (btn.dataset.sortOrder || '').toString();
            const isActive = order === activeOrder;
            btn.classList.toggle('btn-secondary', isActive);
            btn.classList.toggle('btn-outline-secondary', !isActive);
        });
    };

    const pollAgentJob = async (jobUuid) => {
        if (!jobUuid) return;
        try {
            const res = await fetch(`{{ url('/supermarket-comparator/fill-cart/jobs') }}/${encodeURIComponent(jobUuid)}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                },
            });
            const payload = await parseResponseJson(res);
            if (!res.ok) {
                const message = payload && payload.message ? payload.message : 'No se pudo consultar el estado del job.';
                setFillCartFeedback(message, true);
                return;
            }

            const job = payload && payload.job ? payload.job : null;
            if (!job) {
                setFillCartFeedback('No se recibió información del job.', true);
                return;
            }

            if (job.status === 'pending') {
                setFillCartFeedback('En cola: esperando que el agente local tome el trabajo...', false);
                fillCartPollTimer = setTimeout(() => pollAgentJob(jobUuid), 2500);
                return;
            }

            if (job.status === 'in_progress') {
                const progress = job.progress || {};
                const current = progress.current || 0;
                const total = progress.total || 0;
                const title = progress.title || '';
                const progressText = total > 0 ? `${current}/${total}` : 'procesando';
                setFillCartFeedback(`Agente local en progreso (${progressText}) ${title}`.trim(), false);
                fillCartPollTimer = setTimeout(() => pollAgentJob(jobUuid), 2500);
                return;
            }

            if (job.status === 'completed') {
                const result = job.result || {};
                const addedList = Array.isArray(result.added) ? result.added : [];
                const failedList = Array.isArray(result.failed) ? result.failed : [];
                const added = addedList.length;
                const failed = failedList.length;
                let message = `Proceso terminado. Agregados: ${added}. Fallidos: ${failed}.`;
                if (failedList.length) {
                    const details = failedList.map((item) => {
                        const title = (item.title || item.url || 'Producto').toString();
                        const reason = (item.reason || 'Error').toString();
                        return `${title}: ${reason}`;
                    }).join(' | ');
                    message = `${message} ${details}`;
                }
                setFillCartFeedback(message, failed > 0);
                return;
            }

            if (job.status === 'failed') {
                const reason = job.error_message || 'Error desconocido en agente local.';
                setFillCartFeedback(`Proceso fallido: ${reason}`, true);
                return;
            }

            setFillCartFeedback(`Estado de job no reconocido: ${job.status}`, true);
        } catch (error) {
            console.error(error);
            setFillCartFeedback('No se pudo consultar el estado del agente local.', true);
        }
    };

    const showSaveFeedback = (container, message, isError) => {
        if (!container) return;
        let alert = container.querySelector('.smc-save-feedback');
        if (!alert) {
            alert = document.createElement('div');
            alert.className = 'smc-save-feedback alert mt-2';
            const target = container.querySelector('.smc-global-content') || container;
            target.prepend(alert);
        }
        alert.classList.remove('alert-success', 'alert-danger');
        alert.classList.add(isError ? 'alert-danger' : 'alert-success');
        alert.textContent = message;
    };

    document.addEventListener('click', function (e) {
        const sortBtn = e.target.closest('.smc-sort-action');
        if (!sortBtn) return;
        const comparisonBlock = sortBtn.closest('.smc-comparison-block');
        const order = (sortBtn.dataset.sortOrder || '').toString();
        if (!comparisonBlock || (order !== 'cheap' && order !== 'expensive')) return;
        applyComparisonSort(comparisonBlock, order);
        setSortButtonState(comparisonBlock, order);
        refreshSelections();
    });

    document.addEventListener('click', async function (e) {
        const btn = e.target.closest('.smc-save-purchase');
        if (!btn) return;
        const summary = document.getElementById('smc-global-selection');
        const items = collectSelectedItems(resultsWrap || summary);
        if (!items.length) {
            showSaveFeedback(summary, 'Selecciona al menos un producto para guardar.', true);
            return;
        }
        const nameInput = summary ? summary.querySelector('.smc-purchase-name') : null;
        const queryText = document.querySelector('textarea[name="queries"]')?.value || '';
        const stores = Array.from(document.querySelectorAll('input[name="stores[]"]:checked')).map((el) => el.value);
        try {
            btn.disabled = true;
            if (typeof showLoader === 'function') showLoader();
            const res = await fetch("{{ route('supermarket-comparator.purchases.store') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    queries: queryText,
                    name: nameInput?.value || '',
                    stores: stores,
                    items: items,
                }),
            });
            if (!res.ok) throw new Error('No se pudo guardar la compra');
            const data = await res.json();
            showSaveFeedback(summary, 'Compra guardada correctamente.', false);
            if (data && data.purchase && data.purchase.url) {
                const list = document.getElementById('smc-saved-purchases');
                if (list) {
                    const item = document.createElement('div');
                    item.className = 'list-group-item';
                    item.innerHTML = `
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="font-weight-bold">${data.purchase.label || 'Compra guardada'}</div>
                                <small class="text-muted">Nueva</small>
                            </div>
                            <span class="badge badge-light">Hoy</span>
                        </div>
                        <div class="mt-2">
                            <a href="${data.purchase.url}" class="btn btn-sm btn-outline-primary mr-2 mb-2">
                                <i class="fas fa-eye mr-1"></i> Ver
                            </a>
                        </div>
                    `;
                    list.prepend(item);
                }
            }
        } catch (error) {
            console.error(error);
            showSaveFeedback(summary, 'No se pudo guardar la compra. Intenta nuevamente.', true);
        } finally {
            btn.disabled = false;
            if (typeof hideLoader === 'function') hideLoader();
        }
    });

    document.addEventListener('click', async function (e) {
        const openBtn = e.target.closest('.smc-fill-cart');
        if (openBtn) {
            const items = collectSelectedItems(resultsWrap || globalSummary);
            if (!items.length) {
                if (globalSummary) {
                    showSaveFeedback(globalSummary, 'Selecciona al menos un producto para llenar el carrito.', true);
                } else {
                    alert('Selecciona al menos un producto para llenar el carrito.');
                }
                return;
            }
            openFillCartModal(items);
            return;
        }

        if (e.target.closest('.smc-fill-cart-close') || e.target.closest('.smc-fill-cart-cancel')) {
            closeFillCartModal();
            return;
        }

        if (fillCartBackdrop && e.target === fillCartBackdrop) {
            closeFillCartModal();
            return;
        }

        const runBtn = e.target.closest('.smc-fill-cart-run');
        if (!runBtn) return;
        if (!fillCartStoreSelect) return;

        const store = (fillCartStoreSelect.value || '').toString();
        const deviceId = (fillCartDeviceInput?.value || '').trim();
        const items = fillCartItems.filter((item) => (item.store || '').toString().toLowerCase() === store && item.url);
        if (!items.length) {
            setFillCartFeedback('No hay productos seleccionados para esa tienda.', true);
            return;
        }
        if (!deviceId) {
            setFillCartFeedback('Ingresa el device_id del agente local.', true);
            return;
        }
        localStorage.setItem(fillCartDeviceStorageKey, deviceId);

        try {
            runBtn.disabled = true;
            if (fillCartPollTimer) {
                clearTimeout(fillCartPollTimer);
                fillCartPollTimer = null;
            }
            setFillCartFeedback('Encolando trabajo para el agente local...', false);
            if (typeof showLoader === 'function') showLoader();
            const res = await fetch("{{ route('supermarket-comparator.fill-cart') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    store: store,
                    device_id: deviceId,
                    items: items,
                }),
            });
            const payload = await parseResponseJson(res);
            if (!res.ok) {
                let message = 'No se pudo completar el llenado del carrito.';
                if (payload && payload.message) message = payload.message;
                if (payload && payload.error) message = `${message} (${payload.error})`;
                if (payload && payload.raw) message = `${message} (${payload.raw})`;
                throw new Error(message);
            }
            const data = payload || {};
            const jobUuid = data && data.job ? data.job.uuid : null;
            if (!jobUuid) {
                throw new Error('No se recibió job UUID del backend.');
            }
            setFillCartFeedback(data.message || 'Trabajo encolado. Esperando al agente local...', false);
            fillCartPollTimer = setTimeout(() => pollAgentJob(jobUuid), 1200);
        } catch (error) {
            console.error(error);
            setFillCartFeedback(error.message || 'Error al llenar el carrito.', true);
        } finally {
            runBtn.disabled = false;
            if (typeof hideLoader === 'function') hideLoader();
        }
    });

    if (resultsWrap) {
        resultsWrap.addEventListener('change', function (e) {
            if (!e.target.closest('.smc-selection-scope') && !e.target.classList.contains('smc-select-item')) return;
            updateGlobalSelectionSummary();
        });
        resultsWrap.addEventListener('input', function (e) {
            if (!e.target.closest('.smc-selection-scope')) return;
            updateGlobalSelectionSummary();
        });
        refreshSelections();
    }
});
</script>
@endpush
@endsection
