@extends('layouts.public')

@section('title', 'Compra guardada - Comparador supermercados')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2 align-items-center">
            <div class="col-sm-8">
                <h1 class="m-0"><i class="fas fa-save text-secondary mr-2"></i> Compra guardada</h1>
                <small class="text-muted">{{ $purchase->label }}</small>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="card card-outline card-secondary">
            <div class="card-body">
                <div class="mb-3">
                    <div class="font-weight-bold">Totales</div>
                    <div class="table-responsive mt-2">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr class="text-muted">
                                    <th>Tienda</th>
                                    <th class="text-right">Total normal</th>
                                    <th class="text-right">Total tarjeta</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach (($totals['stores'] ?? []) as $row)
                                    <tr>
                                        <td>{{ $row['label'] ?? '-' }}</td>
                                        <td class="text-right">S/ {{ number_format((float) ($row['normal'] ?? 0), 2) }}</td>
                                        <td class="text-right">S/ {{ number_format((float) ($row['card'] ?? 0), 2) }}</td>
                                    </tr>
                                @endforeach
                                @if (empty($totals['stores'] ?? []))
                                    <tr>
                                        <td colspan="3" class="text-muted">Sin datos</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Tienda</th>
                                <th>Producto</th>
                                <th class="text-right">Cantidad</th>
                                <th class="text-right">Precio</th>
                                <th class="text-right">Tarjeta</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $row)
                                <tr>
                                    <td class="text-uppercase">{{ $row->store ?? '-' }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if (!empty($row->image_url))
                                                <img src="{{ $row->image_url }}" alt="img" class="img-size-50 mr-2" style="object-fit:cover;">
                                            @endif
                                            <div>
                                                @if (!empty($row->url))
                                                    <a class="font-weight-bold" href="{{ $row->url }}" target="_blank" rel="noopener">{{ $row->title ?? '-' }}</a>
                                                @else
                                                    <div class="font-weight-bold">{{ $row->title ?? '-' }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-right">{{ number_format((float) $row->quantity, 2) }} {{ $row->unit }}</td>
                                    <td class="text-right">S/ {{ number_format((float) $row->price, 2) }}</td>
                                    <td class="text-right">
                                        @if (!empty($row->card_price))
                                            S/ {{ number_format((float) $row->card_price, 2) }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            @if ($items->isEmpty())
                                <tr>
                                    <td colspan="5" class="text-muted">Sin productos</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                <div class="mt-2">
                    <a class="btn btn-outline-secondary mr-2" href="{{ route('supermarket-comparator.index', ['purchase' => $purchase->uuid]) }}">
                        <i class="fas fa-edit mr-1"></i> Editar compra
                    </a>
                    <a class="btn btn-outline-primary mr-2" href="{{ route('supermarket-comparator.purchases.run', $purchase->uuid) }}">
                        <i class="fas fa-play mr-1"></i> Comparar de nuevo
                    </a>
                    <form class="d-inline" action="{{ route('supermarket-comparator.purchases.delete', $purchase->uuid) }}" method="POST" onsubmit="return confirm('¿Eliminar esta compra?');">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-outline-danger mr-2" type="submit">
                            <i class="fas fa-trash mr-1"></i> Eliminar
                        </button>
                    </form>
                    <a class="btn btn-outline-secondary" href="{{ route('supermarket-comparator.index') }}">
                        <i class="fas fa-arrow-left mr-1"></i> Volver al comparador
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection



