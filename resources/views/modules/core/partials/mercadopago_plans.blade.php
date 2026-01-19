@php
    $planPrices = $planPrices ?? [];
    $plans = [
        'basic' => ['label' => 'Basico', 'price' => (float) ($planPrices['basic'] ?? config('mercadopago.plans.basic', 0))],
        'pro' => ['label' => 'Pro', 'price' => (float) ($planPrices['pro'] ?? config('mercadopago.plans.pro', 0))],
    ];
    $currency = config('mercadopago.currency', 'PEN');
    $planDetails = $planDetails ?? [];
@endphp
<div class="fx-mp-wrap mt-3">
    <div class="fx-mp-head">
        <div>
            <div class="fx-mp-kicker">Mercado Pago</div>
            <h3 class="fx-mp-title"><i class="fas fa-credit-card mr-1"></i> Planes</h3>
            <p class="fx-mp-subtitle">Activa el plan ideal y paga de forma segura.</p>
        </div>
        <div class="fx-mp-chip"><i class="fas fa-shield-alt"></i> Compra protegida</div>
    </div>
    <div class="fx-mp-grid">
        @foreach ($plans as $code => $plan)
            <div class="fx-mp-card {{ $code === 'pro' ? 'is-pro' : '' }}">
                @if ($code === 'pro')
                    <div class="fx-mp-badge"><i class="fas fa-star"></i> Recomendado</div>
                @endif
                <div class="fx-mp-name">{{ $plan['label'] }}</div>
                <div class="fx-mp-price">
                    S/ {{ number_format($plan['price'], 2) }}
                    <span class="fx-mp-period">/ mes</span>
                </div>
                @if (!empty($planDetails[$code]))
                    <ul class="fx-mp-list">
                        @foreach ($planDetails[$code] as $detail)
                            <li><i class="fas fa-check"></i> {{ $detail }}</li>
                        @endforeach
                    </ul>
                @endif
                <form method="POST" action="{{ route('payments.utility.create', $utility->slug ?? 'unknown') }}">
                    @csrf
                    <input type="hidden" name="plan" value="{{ $code }}">
                    <button class="fx-mp-cta" type="submit">
                        <i class="fas fa-lock"></i> Suscribete a plan {{ $plan['label'] }}
                    </button>
                </form>
            </div>
        @endforeach
    </div>
    <div class="fx-mp-foot">
        <div class="fx-mp-foot-left">
            <small class="text-muted">Pago por utilitario: {{ $utility->name ?? $utility->slug ?? 'N/A' }}.</small>
            <span class="fx-mp-note"><i class="fas fa-bolt"></i> Activacion inmediata</span>
        </div>
        <div class="fx-mp-wallets" aria-label="Metodos alternativos">
            <span class="fx-mp-wallets-label">Aceptamos</span>
            <img src="{{ asset('images/payments/yape.png') }}" alt="Yape" class="fx-mp-wallet-icon">
            <img src="{{ asset('images/payments/plin.png') }}" alt="Plin" class="fx-mp-wallet-icon">
        </div>
    </div>
</div>

@push('page_css')
<style>
.fx-mp-wrap {
    background: var(--fx-surface, #ffffff);
    border: 1px solid var(--fx-border, rgba(15, 23, 42, 0.08));
    border-radius: 20px;
    padding: 20px;
    box-shadow: var(--fx-shadow-sm, 0 8px 20px rgba(15, 23, 42, 0.08));
}
.fx-mp-head {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 16px;
}
.fx-mp-kicker {
    text-transform: uppercase;
    letter-spacing: 0.18em;
    font-size: 0.65rem;
    font-weight: 700;
    color: var(--fx-primary, #2563eb);
}
.fx-mp-title {
    margin: 4px 0 4px;
    font-family: 'Fraunces', serif;
    font-weight: 700;
    color: var(--fx-ink, #0f172a);
}
.fx-mp-subtitle {
    margin: 0;
    color: var(--fx-muted, #64748b);
}
.fx-mp-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 999px;
    font-size: 0.8rem;
    font-weight: 600;
    background: rgba(22, 163, 74, 0.12);
    color: var(--fx-success, #16a34a);
    height: fit-content;
}
.fx-mp-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
}
.fx-mp-card {
    position: relative;
    border-radius: 16px;
    border: 1px solid var(--fx-border, rgba(15, 23, 42, 0.08));
    padding: 18px;
    background: var(--fx-surface, #ffffff);
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
    overflow: hidden;
}
.fx-mp-card.is-pro {
    background: linear-gradient(140deg, rgba(37, 99, 235, 0.08), rgba(22, 163, 74, 0.08));
    border-color: rgba(37, 99, 235, 0.25);
}
.fx-mp-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    font-size: 0.7rem;
    font-weight: 700;
    padding: 4px 8px;
    border-radius: 999px;
    background: #111827;
    color: #fff;
}
.fx-mp-name {
    font-weight: 700;
    color: var(--fx-ink, #0f172a);
}
.fx-mp-price {
    font-family: 'Fraunces', serif;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--fx-ink, #0f172a);
    margin: 6px 0 10px;
}
.fx-mp-period {
    font-size: 0.85rem;
    color: var(--fx-muted, #64748b);
    margin-left: 4px;
}
.fx-mp-list {
    list-style: none;
    padding: 0;
    margin: 0 0 14px;
    color: var(--fx-ink-soft, #1f2937);
    font-size: 0.9rem;
}
.fx-mp-list li {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 6px;
}
.fx-mp-list i {
    color: var(--fx-success, #16a34a);
}
.fx-mp-cta {
    width: 100%;
    border: none;
    border-radius: 12px;
    padding: 10px 14px;
    font-weight: 600;
    background: linear-gradient(120deg, var(--fx-success, #16a34a), var(--fx-primary, #2563eb));
    color: #fff;
    cursor: pointer;
}
.fx-mp-cta:hover {
    filter: brightness(1.05);
}
.fx-mp-foot {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 14px;
}
.fx-mp-foot-left {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.fx-mp-wallets {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 0.8rem;
    color: var(--fx-muted, #64748b);
}
.fx-mp-wallets-label {
    font-weight: 600;
}
.fx-mp-wallet-icon {
    height: 32px;
    width: auto;
    display: inline-block;
}
.fx-mp-note {
    font-size: 0.8rem;
    color: var(--fx-muted, #64748b);
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
</style>
@endpush



