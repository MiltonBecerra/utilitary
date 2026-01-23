@php
    $r = $result ?? [];
    $needsRefine = (bool) ($r['needs_refinement'] ?? false);
    $ctx = $r['context_token'] ?? null;
    $q = $r['query'] ?? '';
    $suggested = is_array($r['suggested_refinement'] ?? null) ? $r['suggested_refinement'] : [];
    $errorStores = is_array($r['error_store_codes'] ?? null) ? $r['error_store_codes'] : [];
    $storeCounts = [];
    foreach (($r['candidates'] ?? []) as $storeCode => $items) {
        $storeCounts[$storeCode] = is_array($items) ? count($items) : 0;
    }
@endphp

<div class="card card-outline smc-result-card {{ $needsRefine ? 'card-info' : 'card-success' }}" data-context-token="{{ $ctx }}">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas {{ $needsRefine ? 'fa-filter' : 'fa-balance-scale' }} mr-1"></i>
            {{ $needsRefine ? 'Refinar antes de comparar' : 'Listo para comparar' }}
        </h3>
        <div class="card-tools">
            <span class="badge badge-light">Candidatos: {{ array_sum($storeCounts) }}</span>
        </div>
    </div>
    <div class="card-body">
        <div class="mb-2">
            <div class="font-weight-bold">{{ $q }}</div>
            <div class="text-muted small">
                @foreach ($storeCounts as $s => $c)
                    <span class="badge badge-secondary mr-1 text-uppercase">{{ $s }}: {{ $c }}</span>
                @endforeach
            </div>
        </div>

        @if (array_sum($storeCounts) === 0 && empty($r['errors'] ?? []))
            <div class="callout callout-warning">
                <div class="font-weight-bold mb-1">No se encontraron productos</div>
                <div class="text-muted">Prueba con una bÇ§squeda mÇ­s corta (ej: marca + producto + tamaÇño) o sin detalles extra.</div>
            </div>
        @endif

        @if (!empty($r['errors'] ?? []))
            <div class="callout callout-warning">
                <div class="font-weight-bold mb-1">Resultados parciales</div>
                <ul class="mb-0">
                    @foreach (($r['errors'] ?? []) as $store => $msg)
                        <li><strong>{{ $store }}:</strong> {{ $msg }}</li>
                    @endforeach
                </ul>
                <button class="btn btn-sm btn-outline-warning mt-2 smc-retry-search" type="button" data-query="{{ $q }}" data-stores="{{ implode(',', $errorStores) }}" onclick="window.smcRetrySearch && window.smcRetrySearch(this)">
                    <i class="fas fa-sync-alt mr-1"></i> Reintentar scrapeo
                </button>
            </div>
        @endif

        @if ($needsRefine && !empty($r['ambiguity']['reasons'] ?? []))
            <div class="callout callout-info">
                <p class="mb-1"><strong>La bÇ§squeda es ambigua</strong> y no se compararÇ­ directamente.</p>
                <ul class="mb-0">
                    @foreach (($r['ambiguity']['reasons'] ?? []) as $reason)
                        <li>{{ $reason }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form class="smc-compare-form" action="{{ route('supermarket-comparator.compare') }}" method="POST" data-guest-consent="required">
            @csrf
            @if (!empty($editingPurchase))
                <input type="hidden" name="purchase_uuid" value="{{ $editingPurchase->uuid }}">
            @endif
            <input type="hidden" name="context_token" value="{{ $ctx }}">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Marca (opcional)</label>
                    <input type="text" class="form-control" name="brand" value="{{ (string) ($suggested['brand'] ?? '') }}" placeholder="Ej: Gloria">
                </div>
                <div class="form-group col-md-6">
                    <label>TamaÇño / PresentaciÇün (opcional)</label>
                    <input type="text" class="form-control" name="size" value="{{ (string) ($suggested['size'] ?? '') }}" placeholder="Ej: 1 L, 900 g, 6x330 ml">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Variante (opcional)</label>
                    <input type="text" class="form-control" name="variant" value="{{ (string) ($suggested['variant'] ?? '') }}" placeholder="Ej: descremada, light, original">
                </div>
                <div class="form-group col-md-6">
                    <label>PÇ§blico objetivo (opcional)</label>
                    <input type="text" class="form-control" name="audience" value="{{ (string) ($suggested['audience'] ?? '') }}" placeholder="Ej: bebÇ¸, adulto, mascota">
                </div>
            </div>
            <div class="form-group mb-0">
                <div class="custom-control custom-checkbox">
                    <input type="hidden" name="allow_similar" value="0">
                    <input class="custom-control-input" type="checkbox" id="allow_similar_{{ $ctx }}" name="allow_similar" value="1" checked>
                    <label class="custom-control-label" for="allow_similar_{{ $ctx }}">Incluir ƒ?oSimilaresƒ?? como alternativas</label>
                </div>
            </div>
            <hr>
            <button class="btn {{ $needsRefine ? 'btn-info' : 'btn-success' }}" type="submit">
                <i class="fas fa-balance-scale mr-1"></i> Comparar
            </button>
        </form>

        <div class="smc-compare-target mt-3" data-context-token="{{ $ctx }}"></div>
    </div>
</div>
