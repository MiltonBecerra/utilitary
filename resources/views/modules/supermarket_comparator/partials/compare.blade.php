@php
    $result = $result ?? null;
    $contextToken = $contextToken ?? null;
@endphp

@if (!is_array($result) || !isset($result['comparison']))
    <div class="text-muted">No hay resultados para mostrar.</div>
@else
    @php
        $refinement = is_array($result['refinement'] ?? null) ? $result['refinement'] : [];
        $identical = $result['comparison']['identical'] ?? [];
        $similar = $result['comparison']['similar'] ?? [];
        $combos = $result['comparison']['combos'] ?? [];
        $errors = $result['errors'] ?? [];
        $collapseId = 'smcRefineEdit_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', (string) ($contextToken ?: 'single'));
    @endphp

    @if (empty($errors) && empty($identical) && empty($similar) && empty($combos))
        <div class="callout callout-warning">
            <div class="font-weight-bold mb-1">No se encontraron productos</div>
            <div class="text-muted">Prueba con una búsqueda más corta o edita el refinamiento para ajustar marca/tamaño/variante.</div>
        </div>
    @endif

    <div class="d-flex align-items-center justify-content-between mb-2">
        <h5 class="mb-0">Resultados</h5>
        @if (!empty($contextToken))
            <button class="btn btn-sm btn-outline-secondary" type="button" data-toggle="collapse" data-target="#{{ $collapseId }}">
                <i class="fas fa-edit mr-1"></i> Editar refinamiento
            </button>
        @endif
    </div>

    @if (!empty($contextToken))
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
                            <label>Tamaño / Presentación (opcional)</label>
                            <input type="text" class="form-control" name="size" value="{{ old('size', (string) ($refinement['size'] ?? '')) }}" placeholder="Ej: 1 L, 900 g, 6x330 ml">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Variante (opcional)</label>
                            <input type="text" class="form-control" name="variant" value="{{ old('variant', (string) ($refinement['variant'] ?? '')) }}" placeholder="Ej: descremada, light, original">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Público objetivo (opcional)</label>
                            <input type="text" class="form-control" name="audience" value="{{ old('audience', (string) ($refinement['audience'] ?? '')) }}" placeholder="Ej: bebé, adulto, mascota">
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
                            <label class="custom-control-label" for="{{ $allowSimilarId }}">Incluir “Similares” como alternativas</label>
                        </div>
                    </div>
                    <hr>
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-sync-alt mr-1"></i> Actualizar comparación
                    </button>
                </form>
            </div>
        </div>
    @endif

    @if (!empty($errors))
        <div class="callout callout-warning">
            <div class="font-weight-bold mb-1">Resultados parciales</div>
            <ul class="mb-0">
                @foreach ($errors as $store => $msg)
                    <li><strong>{{ $store }}:</strong> {{ $msg }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="smc-selection-wrapper">
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

        <h5 class="mb-2">Idénticos (ranking principal)</h5>
    @if (empty($identical))
        <div class="text-muted">Aún no hay resultados idénticos.</div>
    @else
        <div class="table-responsive smc-selection-scope">
            <table class="table table-sm table-striped">
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
                        <tr>
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
            <table class="table table-sm table-striped">
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
                        <tr>
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
                <table class="table table-sm table-striped">
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
                            <tr>
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
@endif



