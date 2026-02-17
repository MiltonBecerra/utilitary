@php
    $phase = $phase ?? 'start';
    $result = $result ?? null;
    $results = $results ?? [];
@endphp

@if (($phase ?? 'start') === 'multi' && !empty($results))
    @foreach ($results as $r)
        @include('modules.supermarket_comparator.partials.result_card', [
            'result' => $r,
            'editingPurchase' => $editingPurchase ?? null,
        ])
        @continue
        @php
            $needsRefine = (bool) ($r['needs_refinement'] ?? false);
            $ctx = $r['context_token'] ?? null;
            $q = $r['query'] ?? '';
            $suggested = is_array($r['suggested_refinement'] ?? null) ? $r['suggested_refinement'] : [];
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
                        <div class="text-muted">Prueba con una búsqueda más corta (ej: marca + producto + tamaño) o sin detalles extra.</div>
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
                    </div>
                @endif

                @if ($needsRefine && !empty($r['ambiguity']['reasons'] ?? []))
                    <div class="callout callout-info">
                        <p class="mb-1"><strong>La búsqueda es ambigua</strong> y no se comparará directamente.</p>
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
                            <label>Tamaño / Presentación (opcional)</label>
                            <input type="text" class="form-control" name="size" value="{{ (string) ($suggested['size'] ?? '') }}" placeholder="Ej: 1 L, 900 g, 6x330 ml">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Variante (opcional)</label>
                            <input type="text" class="form-control" name="variant" value="{{ (string) ($suggested['variant'] ?? '') }}" placeholder="Ej: descremada, light, original">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Público objetivo (opcional)</label>
                            <input type="text" class="form-control" name="audience" value="{{ (string) ($suggested['audience'] ?? '') }}" placeholder="Ej: bebé, adulto, mascota">
                        </div>
                    </div>
                    <div class="form-group mb-0">
                        <div class="custom-control custom-checkbox">
                            <input type="hidden" name="allow_similar" value="0">
                            <input class="custom-control-input" type="checkbox" id="allow_similar_{{ $ctx }}" name="allow_similar" value="1" checked>
                            <label class="custom-control-label" for="allow_similar_{{ $ctx }}">Incluir “Similares” como alternativas</label>
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
    @endforeach
@endif

@if (($phase ?? 'start') === 'refine' && isset($result['needs_refinement']) && $result['needs_refinement'])
    <div class="card card-outline card-info">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-filter mr-1"></i> Refinar antes de comparar</h3>
        </div>
        <div class="card-body">
            @php
                $suggestedSingle = is_array($result['suggested_refinement'] ?? null) ? $result['suggested_refinement'] : [];
            @endphp
            <div class="callout callout-info">
                <p class="mb-1"><strong>La búsqueda es ambigua</strong> y no se comparará directamente.</p>
                @if (!empty($result['ambiguity']['reasons'] ?? []))
                    <ul class="mb-0">
                        @foreach (($result['ambiguity']['reasons'] ?? []) as $reason)
                            <li>{{ $reason }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <form class="smc-compare-form" action="{{ route('supermarket-comparator.compare') }}" method="POST" data-guest-consent="required">
                @csrf
                @if (!empty($editingPurchase))
                    <input type="hidden" name="purchase_uuid" value="{{ $editingPurchase->uuid }}">
                @endif
                <input type="hidden" name="context_token" value="{{ $contextToken }}">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Marca (opcional)</label>
                        <input type="text" class="form-control" name="brand" value="{{ old('brand', (string) ($suggestedSingle['brand'] ?? '')) }}" placeholder="Ej: Gloria">
                    </div>
                    <div class="form-group col-md-6">
                        <label>Tamaño / Presentación (opcional)</label>
                        <input type="text" class="form-control" name="size" value="{{ old('size', (string) ($suggestedSingle['size'] ?? '')) }}" placeholder="Ej: 1 L, 900 g, 6x330 ml">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Variante (opcional)</label>
                        <input type="text" class="form-control" name="variant" value="{{ old('variant', (string) ($suggestedSingle['variant'] ?? '')) }}" placeholder="Ej: descremada, light, original">
                    </div>
                    <div class="form-group col-md-6">
                        <label>Público objetivo (opcional)</label>
                        <input type="text" class="form-control" name="audience" value="{{ old('audience', (string) ($suggestedSingle['audience'] ?? '')) }}" placeholder="Ej: bebé, adulto, mascota">
                    </div>
                </div>
                <div class="form-group mb-0">
                    <div class="custom-control custom-checkbox">
                        <input type="hidden" name="allow_similar" value="0">
                        <input class="custom-control-input" type="checkbox" id="allow_similar" name="allow_similar" value="1" checked>
                        <label class="custom-control-label" for="allow_similar">Incluir “Similares” como alternativas</label>
                    </div>
                </div>
                <hr>
                <button class="btn btn-info" type="submit">
                    <i class="fas fa-balance-scale mr-1"></i> Comparar
                </button>
            </form>
        </div>
    </div>
@endif

@if (($phase ?? 'start') === 'compare' && isset($result['comparison']))
    <div class="card card-outline card-success">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-chart-bar mr-1"></i> Resultados</h3>
        </div>
        <div class="card-body">
            @php
                $refinement = is_array($result['refinement'] ?? null) ? $result['refinement'] : [];
                $identical = $result['comparison']['identical'] ?? [];
                $similar = $result['comparison']['similar'] ?? [];
                $combos = $result['comparison']['combos'] ?? [];
                $collapseId = 'smcRefineEdit_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', (string) ($contextToken ?? 'single'));
            @endphp

            @if (!empty($contextToken))
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h5 class="mb-0">Resultados</h5>
                    <button class="btn btn-sm btn-outline-secondary" type="button" data-toggle="collapse" data-target="#{{ $collapseId }}">
                        <i class="fas fa-edit mr-1"></i> Editar refinamiento
                    </button>
                </div>

                <div class="collapse mb-3" id="{{ $collapseId }}">
                    <div class="card card-body bg-light">
                        <form class="smc-compare-form" action="{{ route('supermarket-comparator.compare') }}" method="POST" data-guest-consent="required">
                            @csrf
                            @if (!empty($editingPurchase))
                                <input type="hidden" name="purchase_uuid" value="{{ $editingPurchase->uuid }}">
                            @endif
                            <input type="hidden" name="context_token" value="{{ $contextToken }}">
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>Marca (opcional)</label>
                                    <input type="text" class="form-control" name="brand" value="{{ old('brand', (string) ($refinement['brand'] ?? '')) }}" placeholder="Ej: Gloria">
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Tamano / Presentacion (opcional)</label>
                                    <input type="text" class="form-control" name="size" value="{{ old('size', (string) ($refinement['size'] ?? '')) }}" placeholder="Ej: 1 L, 900 g, 6x330 ml">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>Variante (opcional)</label>
                                    <input type="text" class="form-control" name="variant" value="{{ old('variant', (string) ($refinement['variant'] ?? '')) }}" placeholder="Ej: descremada, light, original">
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Publico objetivo (opcional)</label>
                                    <input type="text" class="form-control" name="audience" value="{{ old('audience', (string) ($refinement['audience'] ?? '')) }}" placeholder="Ej: bebe, adulto, mascota">
                                </div>
                            </div>
                            <div class="form-group mb-0">
                                @php
                                    $allowSimilarId = 'allow_similar_edit_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', (string) $contextToken);
                                    $allowSimilar = (bool) ($refinement['allow_similar'] ?? true);
                                @endphp
                                <div class="custom-control custom-checkbox">
                                    <input type="hidden" name="allow_similar" value="0">
                                    <input class="custom-control-input" type="checkbox" id="{{ $allowSimilarId }}" name="allow_similar" value="1" {{ $allowSimilar ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="{{ $allowSimilarId }}">Incluir "Similares" como alternativas</label>
                                </div>
                            </div>
                            <hr>
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-sync-alt mr-1"></i> Actualizar comparacion
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            <div class="smc-selection-wrapper smc-comparison-block">
                <div class="smc-selection-summary mb-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="font-weight-bold">Seleccionados: <span class="smc-selected-count">0</span></div>
                    </div>
                    <small class="text-muted">Totales por tienda (normal y tarjeta).</small>
                    <div class="table-responsive mt-2">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr class="text-muted">
                                    <th>Tienda</th>
                                    <th class="text-right">Total normal</th>
                                    <th class="text-right">Total tarjeta</th>
                                </tr>
                            </thead>
                            <tbody class="smc-store-totals-body"></tbody>
                        </table>
                    </div>
                    @if (empty($editingPurchase))
                        <div class="mt-2">
                            <div class="d-flex align-items-center">
                                <input class="form-control form-control-sm mr-2 smc-purchase-name" type="text" maxlength="120" placeholder="Nombre de la compra (opcional)">
                                <button class="btn btn-sm btn-outline-primary smc-save-purchase" type="button">
                                    <i class="fas fa-save mr-1"></i> Guardar compra
                                </button>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="d-flex justify-content-end mb-2">
                    <div class="btn-group btn-group-sm" role="group" aria-label="Ordenar resultados">
                        <button type="button" class="btn btn-outline-secondary smc-sort-action" data-sort-order="cheap">Mas barato</button>
                        <button type="button" class="btn btn-outline-secondary smc-sort-action" data-sort-order="expensive">Mas caro</button>
                    </div>
                </div>

                <h5 class="mb-2">Idénticos (ranking principal)</h5>
            @if (empty($identical))
                <div class="text-muted">Aún no hay resultados idénticos. (Integración de tiendas en progreso o refinamiento insuficiente.)</div>
            @else
                <div class="table-responsive smc-selection-scope">
                    <table class="table table-sm table-striped smc-sortable-table">
                        <thead>
                            <tr>
                                <th class="text-center">Sel</th>
                                <th>Tienda</th>
                                <th>Producto</th>
                                <th class="text-right">Cantidad</th>
                                <th class="text-right">Precio</th>
                                <th class="text-right">Tarjeta</th>
                                <th class="text-right">Unitario</th>
                                <th>Disponibilidad</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($identical as $row)
                                @php $sortPrice = (float) (($row['card_price'] ?? 0) > 0 ? $row['card_price'] : ($row['price'] ?? 0)); @endphp
                                <tr data-sort-price="{{ $sortPrice }}">
                                    <td class="text-center align-middle">
                                        <input class="smc-select-item" type="checkbox" data-store="{{ (string) ($row['store'] ?? '') }}" data-store-label="{{ (string) ($row['store'] ?? '') }}" data-title="{{ (string) ($row['title'] ?? '') }}" data-url="{{ (string) ($row['url'] ?? '') }}" data-image="{{ (string) ($row['image_url'] ?? '') }}" data-price="{{ (float) ($row['price'] ?? 0) }}" data-card-price="{{ (float) ($row['card_price'] ?? 0) }}">
                                    </td>
                                    <td class="text-uppercase">{{ $row['store'] ?? '-' }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if (!empty($row['image_url']))
                                                <img src="{{ $row['image_url'] }}" alt="img" class="img-size-50 mr-2" style="object-fit:cover;">
                                            @endif
                                            <div>
                                                @if (!empty($row['url']))
                                                    <a class="font-weight-bold" href="{{ $row['url'] }}" target="_blank" rel="noopener">{{ $row['title'] ?? '-' }}</a>
                                                @else
                                                    <div class="font-weight-bold">{{ $row['title'] ?? '-' }}</div>
                                                @endif
                                                @if (!empty($row['pack_text']))
                                                    <span class="badge badge-info ml-1">{{ $row['pack_text'] }}</span>
                                                @endif
                                                <div class="text-muted small">{{ $row['explain'] ?? '' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-right align-middle">
                                        <div class="d-flex justify-content-end">
                                            <input class="form-control form-control-sm smc-qty" type="number" min="0" step="0.01" value="1" style="max-width:80px;">
                                            <select class="form-control form-control-sm smc-unit ml-1" style="max-width:80px;">
                                                <option value="un">un</option>
                                                <option value="kg">kg</option>
                                                <option value="g">g</option>
                                                <option value="l">l</option>
                                                <option value="ml">ml</option>
                                            </select>
                                        </div>
                                    </td>
                                    <td class="text-right">S/ {{ number_format((float) ($row['price'] ?? 0), 2) }}</td>
                                    <td class="text-right">
                                        @if (!empty($row['card_price']))
                                            <div>S/ {{ number_format((float) $row['card_price'], 2) }}</div>
                                            <div class="text-muted small">{{ $row['card_label'] ?? 'Tarjeta' }}</div>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-right">{{ $row['unit_price_text'] ?? '-' }}</td>
                                    <td>{{ ($row['in_stock'] ?? true) ? 'Disponible' : 'Sin stock' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <hr>

            <h5 class="mb-2">Similares (alternativas)</h5>
            @if (empty($similar))
                <div class="text-muted">No hay alternativas similares para mostrar.</div>
            @else
                <div class="table-responsive smc-selection-scope">
                    <table class="table table-sm table-striped smc-sortable-table">
                        <thead>
                            <tr>
                                <th class="text-center">Sel</th>
                                <th>Tienda</th>
                                <th>Producto</th>
                                <th class="text-right">Cantidad</th>
                                <th class="text-right">Precio</th>
                                <th class="text-right">Tarjeta</th>
                                <th class="text-right">Unitario</th>
                                <th>Por qué es similar</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($similar as $row)
                                @php $sortPrice = (float) (($row['card_price'] ?? 0) > 0 ? $row['card_price'] : ($row['price'] ?? 0)); @endphp
                                <tr data-sort-price="{{ $sortPrice }}">
                                    <td class="text-center align-middle">
                                        <input class="smc-select-item" type="checkbox" data-store="{{ (string) ($row['store'] ?? '') }}" data-store-label="{{ (string) ($row['store'] ?? '') }}" data-title="{{ (string) ($row['title'] ?? '') }}" data-url="{{ (string) ($row['url'] ?? '') }}" data-image="{{ (string) ($row['image_url'] ?? '') }}" data-price="{{ (float) ($row['price'] ?? 0) }}" data-card-price="{{ (float) ($row['card_price'] ?? 0) }}">
                                    </td>
                                    <td class="text-uppercase">{{ $row['store'] ?? '-' }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if (!empty($row['image_url']))
                                                <img src="{{ $row['image_url'] }}" alt="img" class="img-size-50 mr-2" style="object-fit:cover;">
                                            @endif
                                            <div>
                                                @if (!empty($row['url']))
                                                    <a class="font-weight-bold" href="{{ $row['url'] }}" target="_blank" rel="noopener">{{ $row['title'] ?? '-' }}</a>
                                                @else
                                                    <div class="font-weight-bold">{{ $row['title'] ?? '-' }}</div>
                                                @endif
                                                @if (!empty($row['pack_text']))
                                                    <span class="badge badge-info ml-1">{{ $row['pack_text'] }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-right align-middle">
                                        <div class="d-flex justify-content-end">
                                            <input class="form-control form-control-sm smc-qty" type="number" min="0" step="0.01" value="1" style="max-width:80px;">
                                            <select class="form-control form-control-sm smc-unit ml-1" style="max-width:80px;">
                                                <option value="un">un</option>
                                                <option value="kg">kg</option>
                                                <option value="g">g</option>
                                                <option value="l">l</option>
                                                <option value="ml">ml</option>
                                            </select>
                                        </div>
                                    </td>
                                    <td class="text-right">S/ {{ number_format((float) ($row['price'] ?? 0), 2) }}</td>
                                    <td class="text-right">
                                        @if (!empty($row['card_price']))
                                            <div>S/ {{ number_format((float) $row['card_price'], 2) }}</div>
                                            <div class="text-muted small">{{ $row['card_label'] ?? 'Tarjeta' }}</div>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-right">{{ $row['unit_price_text'] ?? '-' }}</td>
                                    <td class="text-muted">{{ $row['explain'] ?? '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

                @if (!empty($combos))
                    <hr>
                    <h5 class="mb-2">Combos / promociones condicionadas (informativo)</h5>
                    <div class="table-responsive smc-selection-scope">
                        <table class="table table-sm table-striped smc-sortable-table">
                            <thead>
                                <tr>
                                    <th class="text-center">Sel</th>
                                    <th>Tienda</th>
                                    <th>Producto</th>
                                    <th class="text-right">Cantidad</th>
                                    <th class="text-right">Precio</th>
                                    <th class="text-right">Tarjeta</th>
                                    <th>Nota</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($combos as $row)
                                    @php $sortPrice = (float) (($row['card_price'] ?? 0) > 0 ? $row['card_price'] : ($row['price'] ?? 0)); @endphp
                                    <tr data-sort-price="{{ $sortPrice }}">
                                        <td class="text-center align-middle">
                                        <input class="smc-select-item" type="checkbox" data-store="{{ (string) ($row['store'] ?? '') }}" data-store-label="{{ (string) ($row['store'] ?? '') }}" data-title="{{ (string) ($row['title'] ?? '') }}" data-url="{{ (string) ($row['url'] ?? '') }}" data-image="{{ (string) ($row['image_url'] ?? '') }}" data-price="{{ (float) ($row['price'] ?? 0) }}" data-card-price="{{ (float) ($row['card_price'] ?? 0) }}">
                                        </td>
                                        <td class="text-uppercase">{{ $row['store'] ?? '-' }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @if (!empty($row['image_url']))
                                                    <img src="{{ $row['image_url'] }}" alt="img" class="img-size-50 mr-2" style="object-fit:cover;">
                                                @endif
                                                <div>
                                                    @if (!empty($row['url']))
                                                        <a class="font-weight-bold" href="{{ $row['url'] }}" target="_blank" rel="noopener">{{ $row['title'] ?? '-' }}</a>
                                                    @else
                                                        <div class="font-weight-bold">{{ $row['title'] ?? '-' }}</div>
                                                    @endif
                                                    <div class="text-muted small">{{ $row['promo_text'] ?? '' }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-right align-middle">
                                            <div class="d-flex justify-content-end">
                                                <input class="form-control form-control-sm smc-qty" type="number" min="0" step="0.01" value="1" style="max-width:80px;">
                                                <select class="form-control form-control-sm smc-unit ml-1" style="max-width:80px;">
                                                    <option value="un">un</option>
                                                    <option value="kg">kg</option>
                                                    <option value="g">g</option>
                                                    <option value="l">l</option>
                                                    <option value="ml">ml</option>
                                                </select>
                                            </div>
                                        </td>
                                        <td class="text-right">S/ {{ number_format((float) ($row['price'] ?? 0), 2) }}</td>
                                        <td class="text-right">
                                            @if (!empty($row['card_price']))
                                                <div>S/ {{ number_format((float) $row['card_price'], 2) }}</div>
                                                <div class="text-muted small">{{ $row['card_label'] ?? 'Tarjeta' }}</div>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="text-muted">{{ $row['explain'] ?? '' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif



