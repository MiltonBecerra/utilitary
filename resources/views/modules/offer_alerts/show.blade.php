@extends('layouts.public')

@section('title', 'Detalle de alerta')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2 align-items-center">
            <div class="col-sm-8">
                <h1 class="m-0"><i class="fas fa-tag text-primary mr-2"></i> Detalle de alerta</h1>
                <small class="text-muted">Historial y configuración de tu alerta.</small>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-8">
                <div class="card card-primary card-outline">
                    <div class="card-body">
                        <div class="d-flex align-items-start">
                            @if($offerAlert->image_url)
                                <img src="{{ $offerAlert->image_url }}" alt="Producto" class="img-fluid img-thumbnail mr-3" style="max-width: 120px;">
                            @else
                                <div class="bg-light d-flex align-items-center justify-content-center mr-3" style="width: 120px; height: 120px; border: 1px solid #dee2e6; border-radius: .25rem;">
                                    <i class="fas fa-image text-muted"></i>
                                </div>
                            @endif
                            <div class="flex-grow-1">
                                <h4 class="mb-1">{!! $offerAlert->title ?? 'Sin título' !!}</h4>
                                <div class="text-muted text-uppercase mb-2">{{ str_replace('_', ' ', $offerAlert->store ?? 'desconocido') }}</div>
                                <a href="{{ $offerAlert->url }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-external-link-alt mr-1"></i> Ver producto
                                </a>
                            </div>
                            @php
                                $statusClass = match ($offerAlert->status) {
                                    'active' => 'primary',
                                    'fallback_email' => 'info',
                                    'inactive' => 'secondary',
                                    'triggered' => 'success',
                                    default => 'info',
                                };
                            @endphp
                            <span class="badge badge-{{ $statusClass }} ml-3">{{ $offerAlert->status }}</span>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="small text-muted">Precio actual</div>
                                @php
                                    // Lógica mejorada para mostrar precio disponible según lo que tenga el producto
                                    if ($offerAlert->price_type === 'cmr') {
                                        $displayPrice = $offerAlert->cmr_price;
                                        $priceTypeLabel = 'CMR';
                                        
                                        // Si no hay precio CMR, mostrar automáticamente el precio público
                                        if ($displayPrice === null && $offerAlert->public_price !== null) {
                                            $displayPrice = $offerAlert->public_price;
                                            $priceTypeLabel = 'Público (CMR no disponible)';
                                        }
                                    } else {
                                        $displayPrice = $offerAlert->public_price;
                                        $priceTypeLabel = 'Público';
                                    }
                                    
                                    // Fallback final
                                    if ($displayPrice === null && $offerAlert->current_price !== null) {
                                        $displayPrice = $offerAlert->current_price;
                                        $priceTypeLabel = 'Precio actual';
                                    }
                                @endphp
                                <div class="h5 mb-0">S/ {{ number_format($displayPrice, 2) }}</div>
                                @if(isset($priceTypeLabel))
                                    <small class="text-muted">{{ $priceTypeLabel }}</small>
                                @endif
                            </div>
                            <div class="col-md-4">
                                <div class="small text-muted">Precio objetivo</div>
                                <div class="h5 mb-0">{{ $offerAlert->target_price ? 'S/ '.number_format($offerAlert->target_price, 2) : '-' }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="small text-muted">Modo</div>
                                <div class="h5 mb-0">{{ $offerAlert->notify_on_any_drop ? 'Cualquier baja' : 'Objetivo' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-chart-line mr-1"></i> Historial de precios</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped mb-0">
                                <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th class="text-right">Precio</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($offerAlert->priceHistories as $history)
                                    <tr>
                                        <td>{{ $history->checked_at }}</td>
                                        <td class="text-right">S/ {{ number_format($history->price, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center text-muted py-3">Aún sin historial</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card card-secondary card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-cog mr-1"></i> Acciones</h3>
                    </div>
                    <div class="card-body">
                        @auth
                            @if(auth()->id() === $offerAlert->user_id)
                                <form action="{{ route('offer-alerts.destroy', $offerAlert->id) }}" method="POST" onsubmit="return confirm('¿Eliminar alerta?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-block">
                                        <i class="fas fa-trash mr-1"></i> Eliminar alerta
                                    </button>
                                </form>
                            @endif
                        @else
                            @if($offerAlert->public_token)
                                <div class="callout callout-info">
                                    <p class="mb-2">Guarda este enlace para administrar tu alerta sin cuenta:</p>
                                    <code class="d-block">{{ route('offer-alerts.public.show', $offerAlert->public_token) }}</code>
                                </div>
                            @endif
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection




