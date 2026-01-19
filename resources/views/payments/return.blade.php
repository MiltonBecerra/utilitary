@extends('layouts.public')

@section('title', 'Pago - Mercado Pago')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="page-title"><i class="fas fa-credit-card text-success"></i> Pago</h1>
                <p class="text-muted mb-0">Estado del pago: <strong>{{ $status }}</strong></p>
            </div>
        </div>
    </div>
</section>
<section class="content">
    <div class="container-fluid">
        @php
            $statusLabel = match ($status) {
                'approved' => 'Pago aprobado. Tu plan se activará en breve.',
                'pending' => 'Pago pendiente. Te avisaremos cuando se confirme.',
                'rejected' => 'Pago rechazado. Intenta nuevamente.',
                default => 'Estado recibido. Si no ves cambios, espera unos minutos.',
            };
        @endphp
        <div class="alert alert-info">{{ $statusLabel }}</div>
        <a class="btn btn-outline-secondary" href="{{ $returnUrl ?? url()->previous() }}">Volver</a>
    </div>
</section>
@endsection
