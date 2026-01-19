@extends('layouts.public')

@section('title', 'Editar compra - Comparador supermercados')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2 align-items-center">
            <div class="col-sm-8">
                <h1 class="m-0"><i class="fas fa-edit text-secondary mr-2"></i> Editar compra</h1>
                <small class="text-muted">{{ $purchase->label }}</small>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="card card-outline card-secondary">
            <div class="card-body">
                <form action="{{ route('supermarket-comparator.purchases.update', $purchase->uuid) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label>Nombre de la compra (opcional)</label>
                        <input type="text" name="name" class="form-control" maxlength="120" value="{{ old('name', $purchase->name) }}" placeholder="Ej: Compra de la semana">
                    </div>
                    <div class="form-group">
                        <label>Productos a buscar</label>
                        <textarea name="queries" class="form-control" rows="4" placeholder="1 producto por linea">{{ old('queries', $queries ?? '') }}</textarea>
                        <small class="text-muted">Cada producto va en una linea distinta.</small>
                    </div>
                    @php
                        $storeOptions = [
                            'plaza_vea' => 'Plaza Vea',
                            'tottus' => 'Tottus',
                            'metro' => 'Metro',
                            'wong' => 'Wong',
                        ];
                        $defaultStores = array_keys($storeOptions);
                        $selectedStores = old('stores', $stores ?? $defaultStores);
                    @endphp
                    <div class="form-group">
                        <label>Supermercados</label>
                        <div class="d-flex flex-wrap">
                            @foreach ($storeOptions as $code => $label)
                                @php $checked = in_array($code, $selectedStores ?? [], true); @endphp
                                <div class="custom-control custom-checkbox mr-3 mb-1">
                                    <input class="custom-control-input" type="checkbox" id="store_edit_{{ $code }}" name="stores[]" value="{{ $code }}" {{ $checked ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="store_edit_{{ $code }}">{{ $label }}</label>
                                </div>
                            @endforeach
                        </div>
                        <small class="text-muted">La busqueda solo se ejecuta en los supermercados seleccionados.</small>
                    </div>
                    <button class="btn btn-secondary" type="submit">
                        <i class="fas fa-save mr-1"></i> Guardar cambios
                    </button>
                    <a class="btn btn-outline-primary ml-2" href="{{ route('supermarket-comparator.purchases.run', $purchase->uuid) }}">
                        <i class="fas fa-play mr-1"></i> Comparar de nuevo
                    </a>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection



