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
                                <small class="text-muted">Puedes ingresar hasta 10 productos.</small>
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

    const updateSelectionSummary = (wrapper) => {
        if (!wrapper) return;
        const selected = wrapper.querySelectorAll('.smc-select-item:checked');
        const countEl = wrapper.querySelector('.smc-selected-count');
        if (countEl) countEl.textContent = selected.length;

        const totals = {};
        selected.forEach((checkbox) => {
            const row = checkbox.closest('tr');
            const qtyInput = row ? row.querySelector('.smc-qty') : null;
            const qty = parseFloat(qtyInput?.value || '1') || 1;
            const price = parseFloat(checkbox.dataset.price || '0') || 0;
            const cardPrice = parseFloat(checkbox.dataset.cardPrice || '0') || 0;
            const storeLabel = (checkbox.dataset.storeLabel || checkbox.dataset.store || 'OTROS').toString();
            const key = storeLabel.toUpperCase();
            if (!totals[key]) totals[key] = { normal: 0, card: 0 };
            totals[key].normal += price * qty;
            totals[key].card += (cardPrice > 0 ? cardPrice : price) * qty;
        });

        const tbody = wrapper.querySelector('.smc-store-totals-body');
        if (!tbody) return;
        tbody.innerHTML = '';
        Object.keys(totals).forEach((store) => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${store}</td>
                <td class="text-right">S/ ${totals[store].normal.toFixed(2)}</td>
                <td class="text-right">S/ ${totals[store].card.toFixed(2)}</td>
            `;
            tbody.appendChild(row);
        });
        if (!Object.keys(totals).length) {
            const emptyRow = document.createElement('tr');
            emptyRow.innerHTML = '<td colspan="3" class="text-muted">Sin seleccion</td>';
            tbody.appendChild(emptyRow);
        }
    };

    const refreshSelections = (scope) => {
        (scope || document).querySelectorAll('.smc-selection-wrapper').forEach((wrapper) => {
            updateSelectionSummary(wrapper);
        });
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
                refreshSelections(target);
            }
        } catch (error) {
            console.error(error);
            form.submit();
        } finally {
            if (typeof hideLoader === 'function') hideLoader();
        }
    };

    if (searchForm && resultsWrap) {
        searchForm.addEventListener('submit', function (e) {
            if (searchForm.dataset.async !== 'true') return;
            e.preventDefault();
            fetchForm(searchForm, resultsWrap);
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
        const query = (button.dataset.query || '').trim();
        if (!query) return;
        const card = button.closest('.smc-result-card');
        const storeList = (button.dataset.stores || '').split(',').map((s) => s.trim()).filter((s) => s !== '');
        const stores = storeList.length
            ? storeList
            : Array.from(document.querySelectorAll('input[name="stores[]"]:checked')).map((el) => el.value);
        const purchaseUuid = document.querySelector('input[name="purchase_uuid"]')?.value || '';
        const formData = new FormData();
        formData.append('query', query);
        stores.forEach((store) => formData.append('stores[]', store));
        if (purchaseUuid) formData.append('purchase_uuid', purchaseUuid);

        try {
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
            if (!res.ok) throw new Error('Error al reintentar');
            const data = await res.json();
            if (data && data.html && card) {
                card.outerHTML = data.html;
            }
        } catch (error) {
            console.error(error);
            alert('No se pudo reintentar el scrapeo. Intenta nuevamente.');
        } finally {
            button.disabled = false;
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

    const collectSelectedItems = (wrapper) => {
        const items = [];
        wrapper.querySelectorAll('.smc-select-item:checked').forEach((checkbox) => {
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

    const showSaveFeedback = (wrapper, message, isError) => {
        let alert = wrapper.querySelector('.smc-save-feedback');
        if (!alert) {
            alert = document.createElement('div');
            alert.className = 'smc-save-feedback alert mt-2';
            wrapper.prepend(alert);
        }
        alert.classList.remove('alert-success', 'alert-danger');
        alert.classList.add(isError ? 'alert-danger' : 'alert-success');
        alert.textContent = message;
    };

    document.addEventListener('click', async function (e) {
        const btn = e.target.closest('.smc-save-purchase');
        if (!btn) return;
        const wrapper = btn.closest('.smc-selection-wrapper');
        if (!wrapper) return;
        const items = collectSelectedItems(wrapper);
        if (!items.length) {
            showSaveFeedback(wrapper, 'Selecciona al menos un producto para guardar.', true);
            return;
        }
        const nameInput = wrapper.querySelector('.smc-purchase-name');
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
            showSaveFeedback(wrapper, 'Compra guardada correctamente.', false);
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
            showSaveFeedback(wrapper, 'No se pudo guardar la compra. Intenta nuevamente.', true);
        } finally {
            btn.disabled = false;
            if (typeof hideLoader === 'function') hideLoader();
        }
    });

    if (resultsWrap) {
        resultsWrap.addEventListener('change', function (e) {
            if (!e.target.closest('.smc-selection-wrapper')) return;
            updateSelectionSummary(e.target.closest('.smc-selection-wrapper'));
        });
        resultsWrap.addEventListener('input', function (e) {
            if (!e.target.closest('.smc-selection-wrapper')) return;
            updateSelectionSummary(e.target.closest('.smc-selection-wrapper'));
        });
        refreshSelections(resultsWrap);
    }
});
</script>
@endpush
@endsection
