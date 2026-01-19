@extends('layouts.public')

@section('title', 'Sorteo de nombres')

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
.raffle-card .card-header {
    background: linear-gradient(120deg, var(--fx-primary-strong), var(--fx-primary));
    color: #fff;
    border-bottom: none;
}
.raffle-wheel-wrap {
    position: relative;
    width: min(320px, 80vw);
    height: min(320px, 80vw);
    margin: 0 auto 16px;
}
.raffle-pointer {
    position: absolute;
    top: 14px;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 12px solid transparent;
    border-right: 12px solid transparent;
    border-top: 18px solid var(--fx-ink);
    z-index: 3;
}
.raffle-canvas {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    border: 10px solid var(--fx-ink);
    box-shadow: 0 12px 20px rgba(17, 24, 39, 0.2);
    background: var(--fx-surface-soft);
    display: block;
}
.raffle-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.9);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 1050;
}
[data-theme="dark"] .raffle-overlay {
    background: rgba(5, 9, 20, 0.94);
}
.raffle-overlay.is-active {
    display: flex;
}
.raffle-overlay-card {
    background: var(--fx-surface);
    border-radius: 18px;
    padding: 20px;
    width: min(720px, 94vw);
    box-shadow: 0 20px 40px rgba(15, 23, 42, 0.25);
    text-align: center;
    border: 1px solid var(--fx-border);
}
.raffle-overlay .raffle-wheel-wrap {
    width: min(560px, 86vw);
    height: min(560px, 86vw);
    margin-bottom: 12px;
}
.raffle-overlay-actions {
    margin-top: 12px;
    display: flex;
    gap: 8px;
    justify-content: center;
}
.raffle-wheel-label {
    font-size: 1.4rem;
    font-weight: 700;
    text-align: center;
    padding: 10px 12px;
    border-radius: 10px;
    background: var(--fx-ink);
    color: #fff;
    min-height: 52px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.raffle-winners li {
    background: var(--fx-surface-soft);
    border: 1px solid var(--fx-border);
    border-radius: 8px;
    padding: 8px 10px;
    margin-bottom: 8px;
}
.raffle-history li {
    border-left: 3px solid var(--fx-primary);
    padding-left: 10px;
    margin-bottom: 10px;
}
.btn-raffle {
    min-width: 120px;
}
.raffle-danger-btn {
    background: #dc3545;
    border-color: #dc3545;
    color: #ffffff;
    font-weight: 600;
}
.raffle-danger-btn:hover {
    background: #c82333;
    border-color: #c82333;
    color: #ffffff;
}
[data-theme="dark"] .raffle-danger-btn {
    background: #dc3545;
    border-color: #dc3545;
    color: #fff;
}
[data-theme="dark"] .raffle-danger-btn:hover {
    background: #ef4444;
    border-color: #ef4444;
    color: #fff;
}
[data-theme="dark"] .btn-primary {
    background: #3b82f6;
    border-color: #3b82f6;
    box-shadow: var(--fx-glow);
}
[data-theme="dark"] .btn-outline-primary,
[data-theme="dark"] .btn-outline-secondary,
[data-theme="dark"] .btn-outline-danger {
    color: #e2e8f0;
    border-color: rgba(148, 163, 184, 0.35);
}
[data-theme="dark"] .form-control,
[data-theme="dark"] .input-group-text,
[data-theme="dark"] .custom-select {
    background: rgba(15, 23, 42, 0.95);
    border-color: rgba(148, 163, 184, 0.25);
    color: #e2e8f0;
}
[data-theme="dark"] .raffle-canvas {
    border-color: #1f2937;
    background: #0b1220;
    box-shadow: 0 18px 30px rgba(0, 0, 0, 0.45);
}
[data-theme="dark"] .raffle-pointer {
    border-top-color: #e2e8f0;
}
[data-theme="dark"] .raffle-wheel-label {
    background: #111827;
    color: #e2e8f0;
    border: 1px solid rgba(148, 163, 184, 0.25);
}
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
        <div class="fx-card fx-header fx-reveal">
            <div class="fx-header-main">
                <div>
                    <div class="fx-kicker">Sorteo inteligente</div>
                    <h1 class="page-title"><i class="fas fa-random text-primary"></i> Sorteo de nombres</h1>
                    <p class="page-subtitle mb-0">Ingresa nombres manualmente o carga un archivo, define ganadores y guarda resultados.</p>
                </div>
                <div class="fx-header-actions">
                    <a href="#raffle-input" class="btn btn-primary"><i class="fas fa-play"></i> Iniciar sorteo</a>
                    <a href="#raffle-plans" class="btn btn-outline-primary"><i class="fas fa-crown"></i> Ver planes</a>
                    <button type="button" class="btn btn-light fx-theme-toggle" id="theme-toggle" aria-pressed="false">
                        <i class="fas fa-moon"></i> <span class="fx-theme-label">Modo oscuro</span>
                    </button>
                </div>
            </div>
            <div class="row mt-3 g-3">
                <div class="col-md-4">
                    <div class="fx-stat">
                        <i class="fas fa-users"></i>
                        <div>
                            <div class="fx-stat-label">Lista actual</div>
                            <div class="fx-stat-value"><span id="names-stat-count">0</span> nombres</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="fx-stat">
                        <i class="fas fa-trophy"></i>
                        <div>
                            <div class="fx-stat-label">Ganadores</div>
                            <div class="fx-stat-value"><span id="winners-stat-count">0</span> seleccionados</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="fx-stat">
                        <i class="fas fa-save"></i>
                        <div>
                            <div class="fx-stat-label">Historial</div>
                            <div class="fx-stat-value"><span id="history-stat-count">0</span> sorteos</div>
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
            <div class="col-lg-5">
                <div class="card fx-card raffle-card" id="raffle-input">
                    <div class="card-header">
                        <h3 class="card-title">Entrada de nombres</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="names-input">Lista de nombres (uno por linea o separados por coma)</label>
                            <textarea id="names-input" class="form-control" rows="10" placeholder="Ejemplo:\nAna\nLuis\nCarla"></textarea>
                            <small class="form-text text-muted">Total: <span id="names-count">0</span> nombres</small>
                        </div>
                        <div class="form-group">
                            <label for="names-file">Subir archivo (TXT o CSV)</label>
                            <input type="file" id="names-file" class="form-control-file" accept=".txt,.csv" />
                        </div>
                    </div>
                </div>

                <div class="card fx-card raffle-card">
                    <div class="card-header">
                        <h3 class="card-title">Configuracion</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="winners-count">Numero de ganadores</label>
                            <input type="number" min="1" value="1" id="winners-count" class="form-control" />
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="avoid-repeats" checked>
                            <label class="form-check-label" for="avoid-repeats">
                                Evitar repetir ganadores en el sorteo
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="save-results" checked>
                            <label class="form-check-label" for="save-results">
                                Guardar resultados en este navegador
                            </label>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-primary btn-raffle" id="start-raffle">Sortear</button>
                        <button class="btn btn-outline-secondary btn-raffle" id="clear-raffle">Limpiar</button>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card fx-card raffle-card">
                    <div class="card-header">
                        <h3 class="card-title">Resultado</h3>
                    </div>
                    <div class="card-body">
                        <div class="raffle-wheel-wrap">
                            <div class="raffle-pointer"></div>
                            <canvas id="raffle-canvas" class="raffle-canvas" width="320" height="320"></canvas>
                        </div>
                        <div id="raffle-label" class="raffle-wheel-label">Listo para sortear</div>
                        <div class="mt-3">
                            <h5 class="mb-2">Ganadores</h5>
                            <ul class="list-unstyled raffle-winners" id="winners-list">
                                <li class="text-muted">Aun no hay ganadores.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="card fx-card raffle-card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h3 class="card-title">Historial guardado</h3>
                        <button class="btn btn-sm raffle-danger-btn" id="clear-history">
                            Borrar historial
                        </button>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled raffle-history mb-0" id="history-list">
                            <li class="text-muted">Sin resultados guardados.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="fx-section fx-reveal mt-4" id="raffle-plans">
            <div class="d-flex justify-content-between align-items-end flex-wrap mb-2">
                <div>
                    <h2 class="fx-section-title mb-1"><i class="fas fa-credit-card"></i> Planes y pagos</h2>
                    <p class="fx-section-subtitle">Activa el plan que necesitas para tus utilitarios.</p>
                </div>
                <span class="fx-chip"><i class="fas fa-shield-alt"></i> Pago seguro</span>
            </div>
            @include('modules.core.partials.mercadopago_plans', ['utility' => $utility])
        </div>
    </div>

    <div id="raffle-overlay" class="raffle-overlay" aria-hidden="true">
        <div class="raffle-overlay-card">
            <h4 class="mb-2">Sorteo en progreso</h4>
            <div class="raffle-wheel-wrap">
                <div class="raffle-pointer"></div>
                <canvas id="raffle-overlay-canvas" class="raffle-canvas" width="420" height="420"></canvas>
            </div>
            <div id="raffle-overlay-label" class="raffle-wheel-label">Girando la ruleta...</div>
            <div class="raffle-overlay-actions">
                <button id="raffle-close" class="btn btn-outline-secondary" disabled>Cerrar</button>
            </div>
        </div>
    </div>
</section>

<div class="fx-comments fx-reveal">
    @include('modules.core.partials.comments', ['utility' => $utility, 'comments' => $comments])
</div>
@endsection

@push('page_scripts')
<script>
    const namesInput = document.getElementById('names-input');
    const namesFile = document.getElementById('names-file');
    const namesCount = document.getElementById('names-count');
    const statNames = document.getElementById('names-stat-count');
    const statWinners = document.getElementById('winners-stat-count');
    const statHistory = document.getElementById('history-stat-count');
    const winnersCount = document.getElementById('winners-count');
    const avoidRepeats = document.getElementById('avoid-repeats');
    const saveResults = document.getElementById('save-results');
    const startRaffle = document.getElementById('start-raffle');
    const clearRaffle = document.getElementById('clear-raffle');
    const canvas = document.getElementById('raffle-canvas');
    const ctx = canvas.getContext('2d');
    const overlay = document.getElementById('raffle-overlay');
    const overlayCanvas = document.getElementById('raffle-overlay-canvas');
    const overlayCtx = overlayCanvas.getContext('2d');
    const overlayLabel = document.getElementById('raffle-overlay-label');
    const overlayClose = document.getElementById('raffle-close');
    const label = document.getElementById('raffle-label');
    const winnersList = document.getElementById('winners-list');
    const historyList = document.getElementById('history-list');
    const clearHistory = document.getElementById('clear-history');

    const storageKey = 'name_raffle_history_v1';
    const themeToggle = document.getElementById('theme-toggle');
    const themeStorageKey = 'fx-theme';
    const root = document.documentElement;

    const colors = ['#f97316', '#22c55e', '#3b82f6', '#facc15', '#ec4899', '#14b8a6'];
    const pointerAngle = -Math.PI / 2;
    let currentRotation = 0;
    let isSpinning = false;

    const normalizeNames = (raw) => {
        return raw
            .split(/[\r\n,]+/)
            .map((name) => name.trim())
            .filter((name) => name.length > 0);
    };

    const resizeCanvasFor = (targetCanvas, targetCtx) => {
        const size = targetCanvas.getBoundingClientRect().width;
        const scale = window.devicePixelRatio || 1;
        targetCanvas.width = Math.floor(size * scale);
        targetCanvas.height = Math.floor(size * scale);
        targetCtx.setTransform(scale, 0, 0, scale, 0, 0);
        return size;
    };

    const updateCount = () => {
        const names = normalizeNames(namesInput.value || '');
        namesCount.textContent = names.length;
        if (statNames) statNames.textContent = names.length;
        drawWheel(names, currentRotation, ctx, canvas.getBoundingClientRect().width);
    };

    const drawWheel = (names, rotation, targetCtx, size) => {
        const radius = size / 2;
        targetCtx.clearRect(0, 0, size, size);
        if (!names.length) {
            targetCtx.fillStyle = '#f8f9fc';
            targetCtx.beginPath();
            targetCtx.arc(radius, radius, radius - 6, 0, Math.PI * 2);
            targetCtx.fill();
            return;
        }

        const total = names.length;
        const segmentAngle = (Math.PI * 2) / total;
        const fontSize = Math.max(8, Math.min(12, Math.floor(200 / Math.max(total, 10))));
        targetCtx.save();
        targetCtx.translate(radius, radius);
        targetCtx.rotate(rotation);

        for (let i = 0; i < total; i += 1) {
            const start = i * segmentAngle;
            const end = start + segmentAngle;
            targetCtx.fillStyle = colors[i % colors.length];
            targetCtx.beginPath();
            targetCtx.moveTo(0, 0);
            targetCtx.arc(0, 0, radius - 6, start, end);
            targetCtx.closePath();
            targetCtx.fill();

            targetCtx.save();
            targetCtx.rotate(start + segmentAngle / 2);
            targetCtx.textAlign = 'right';
            targetCtx.textBaseline = 'middle';
            targetCtx.fillStyle = '#111827';
            targetCtx.font = `600 ${fontSize}px sans-serif`;
            const label = names[i];
            const maxWidth = radius - 24;
            const clipped = label.length > 30 ? `${label.slice(0, 27)}...` : label;
            targetCtx.fillText(clipped, radius - 12, 0, maxWidth);
            targetCtx.restore();
        }

        targetCtx.restore();
    };

    const renderWinners = (winners) => {
        winnersList.innerHTML = '';
        if (!winners.length) {
            const empty = document.createElement('li');
            empty.className = 'text-muted';
            empty.textContent = 'Aun no hay ganadores.';
            winnersList.appendChild(empty);
            if (statWinners) statWinners.textContent = '0';
            return;
        }

        winners.forEach((winner, index) => {
            const item = document.createElement('li');
            item.textContent = `${index + 1}. ${winner}`;
            winnersList.appendChild(item);
        });
        if (statWinners) statWinners.textContent = winners.length.toString();
    };

    const loadHistory = () => {
        const raw = localStorage.getItem(storageKey);
        const entries = raw ? JSON.parse(raw) : [];
        historyList.innerHTML = '';
        if (!entries.length) {
            const empty = document.createElement('li');
            empty.className = 'text-muted';
            empty.textContent = 'Sin resultados guardados.';
            historyList.appendChild(empty);
            if (statHistory) statHistory.textContent = '0';
            return;
        }
        if (statHistory) statHistory.textContent = entries.length.toString();
        entries.forEach((entry) => {
            const item = document.createElement('li');
            item.innerHTML = `<strong>${entry.date}</strong><div>${entry.winners.join(', ')}</div>`;
            historyList.appendChild(item);
        });
    };

    const saveHistoryEntry = (winners) => {
        if (!saveResults.checked) {
            return;
        }
        const raw = localStorage.getItem(storageKey);
        const entries = raw ? JSON.parse(raw) : [];
        entries.unshift({
            date: new Date().toLocaleString(),
            winners: winners.slice(0, 10),
        });
        localStorage.setItem(storageKey, JSON.stringify(entries.slice(0, 20)));
        loadHistory();
    };

    const pickWinnerEntries = (names, count, uniqueOnly) => {
        const winners = [];
        if (uniqueOnly) {
            const indices = names.map((_, index) => index);
            for (let i = indices.length - 1; i > 0; i -= 1) {
                const swap = Math.floor(Math.random() * (i + 1));
                [indices[i], indices[swap]] = [indices[swap], indices[i]];
            }
            indices.slice(0, count).forEach((index) => {
                winners.push({ index, name: names[index] });
            });
            return winners;
        }
        for (let i = 0; i < count; i += 1) {
            const index = Math.floor(Math.random() * names.length);
            winners.push({ index, name: names[index] });
        }
        return winners;
    };

    const spinToWinner = (winnerIndex, total, names, targetCtx, size) => {
        return new Promise((resolve) => {
            if (total <= 1) {
                resolve(winnerIndex);
                return;
            }
            const segmentAngle = (Math.PI * 2) / total;
            const segmentStart = winnerIndex * segmentAngle;
            const randomOffset = Math.random() * segmentAngle;
            const targetAngle = segmentStart + randomOffset;
            const extraSpins = 6 + Math.floor(Math.random() * 3);
            const target = currentRotation + (extraSpins * Math.PI * 2) + (pointerAngle - targetAngle);
            const start = performance.now();
            const duration = 4200;

            const easeOutCubic = (t) => 1 - Math.pow(1 - t, 3);
            const animate = (now) => {
                const elapsed = now - start;
                const t = Math.min(elapsed / duration, 1);
                const eased = easeOutCubic(t);
                const angle = currentRotation + (target - currentRotation) * eased;
                drawWheel(names, angle, targetCtx, size);
                if (t < 1) {
                    requestAnimationFrame(animate);
                } else {
                    currentRotation = target % (Math.PI * 2);
                    const pointerOnWheel = ((pointerAngle - currentRotation) % (Math.PI * 2) + (Math.PI * 2)) % (Math.PI * 2);
                    const boundaryDistance = pointerOnWheel % segmentAngle;
                    let finalIndex = Math.floor(pointerOnWheel / segmentAngle);
                    const epsilon = segmentAngle * 0.02;
                    if (segmentAngle - boundaryDistance < epsilon) {
                        finalIndex = (finalIndex + 1) % total;
                        currentRotation = (currentRotation - (epsilon * 2)) % (Math.PI * 2);
                        drawWheel(names, currentRotation, targetCtx, size);
                    }
                    resolve(finalIndex);
                }
            };
            requestAnimationFrame(animate);
        });
    };

    const runRaffle = () => {
        if (isSpinning) {
            return;
        }
        const names = normalizeNames(namesInput.value || '');
        const total = names.length;
        const desired = Math.max(parseInt(winnersCount.value || '1', 10), 1);
        const uniqueOnly = avoidRepeats.checked;

        if (!total) {
            alert('Ingresa al menos un nombre.');
            return;
        }

        if (uniqueOnly && desired > new Set(names).size) {
            alert('El numero de ganadores supera los nombres disponibles.');
            return;
        }

        const winners = pickWinnerEntries(names, desired, uniqueOnly);
        const winnerNames = [];
        renderWinners([]);
        label.textContent = 'Girando la ruleta...';
        overlayLabel.textContent = 'Girando la ruleta...';
        isSpinning = true;
        startRaffle.disabled = true;
        overlayClose.disabled = true;
        overlay.classList.add('is-active');
        overlay.setAttribute('aria-hidden', 'false');
        const overlaySize = resizeCanvasFor(overlayCanvas, overlayCtx);
        drawWheel(names, currentRotation, overlayCtx, overlaySize);

        const run = async () => {
            for (const entry of winners) {
                const resolvedIndex = await spinToWinner(entry.index, total, names, overlayCtx, overlaySize);
                const resolvedName = names[resolvedIndex] || 'Sin ganadores';
                winnerNames.push(resolvedName);
                label.textContent = resolvedName;
                overlayLabel.textContent = resolvedName;
            }
            renderWinners(winnerNames);
            saveHistoryEntry(winnerNames);
            isSpinning = false;
            startRaffle.disabled = false;
            overlayClose.disabled = false;
        };

        run();
    };

    startRaffle.addEventListener('click', (event) => {
        if (typeof window.ensureGuestConsent === 'function') {
            const allowed = window.ensureGuestConsent(runRaffle);
            if (!allowed) {
                event.preventDefault();
                return;
            }
        }
        runRaffle();
    });

    clearRaffle.addEventListener('click', () => {
        renderWinners([]);
        label.textContent = 'Listo para sortear';
    });

    clearHistory.addEventListener('click', () => {
        localStorage.removeItem(storageKey);
        loadHistory();
    });

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

    namesInput.addEventListener('input', updateCount);
    window.addEventListener('resize', () => {
        resizeCanvasFor(canvas, ctx);
        drawWheel(normalizeNames(namesInput.value || ''), currentRotation, ctx, canvas.getBoundingClientRect().width);
        if (overlay.classList.contains('is-active')) {
            const overlaySize = resizeCanvasFor(overlayCanvas, overlayCtx);
            drawWheel(normalizeNames(namesInput.value || ''), currentRotation, overlayCtx, overlaySize);
        }
    });
    namesFile.addEventListener('change', (event) => {
        const file = event.target.files[0];
        if (!file) {
            return;
        }
        const reader = new FileReader();
        reader.onload = (e) => {
            const text = String(e.target.result || '');
            const current = namesInput.value.trim();
            const combined = current ? `${current}\n${text.trim()}` : text.trim();
            namesInput.value = combined;
            updateCount();
        };
        reader.readAsText(file);
    });

    overlayClose.addEventListener('click', () => {
        overlay.classList.remove('is-active');
        overlay.setAttribute('aria-hidden', 'true');
        resizeCanvasFor(canvas, ctx);
        drawWheel(normalizeNames(namesInput.value || ''), currentRotation, ctx, canvas.getBoundingClientRect().width);
    });

    resizeCanvasFor(canvas, ctx);
    updateCount();
    loadHistory();
</script>
@endpush



