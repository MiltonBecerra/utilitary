@extends('layouts.public')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-crown"></i> Mi Suscripción</h4>
                </div>
                <div class="card-body">
                    @if($currentSubscription)
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5>Plan Actual</h5>
                                <div class="alert alert-info">
                                    <h3 class="mb-0">
                                        @if($currentSubscription->plan_type == 'free')
                                            <i class="fas fa-gift"></i> Free
                                        @elseif($currentSubscription->plan_type == 'basic')
                                            <i class="fas fa-star"></i> Basic
                                        @else
                                            <i class="fas fa-crown"></i> Pro
                                        @endif
                                    </h3>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h5>Estado</h5>
                                <div class="alert {{ $currentSubscription->isActive() ? 'alert-success' : 'alert-danger' }}">
                                    @if($currentSubscription->isActive())
                                        <i class="fas fa-check-circle"></i> Activo
                                        <p class="mb-0 mt-2">
                                            <small>Válido hasta: <strong>{{ $currentSubscription->ends_at->format('d/m/Y') }}</strong></small><br>
                                            <small>{{ $currentSubscription->daysRemaining() }} días restantes</small>
                                        </p>
                                    @else
                                        <i class="fas fa-times-circle"></i> Expirado
                                        <p class="mb-0 mt-2">
                                            <small>Expiró el: {{ $currentSubscription->ends_at->format('d/m/Y') }}</small>
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-12">
                                <h5>Beneficios de tu Plan</h5>
                                <ul class="list-group">
                                    @if($currentSubscription->plan_type == 'free')
                                        <li class="list-group-item"><i class="fas fa-check text-success"></i> 1 alerta activa</li>
                                        <li class="list-group-item"><i class="fas fa-check text-success"></i> Notificaciones por Email</li>
                                        <li class="list-group-item"><i class="fas fa-times text-danger"></i> Alertas de una sola vez</li>
                                    @elseif($currentSubscription->plan_type == 'basic')
                                        <li class="list-group-item"><i class="fas fa-check text-success"></i> Hasta 5 alertas activas</li>
                                        <li class="list-group-item"><i class="fas fa-check text-success"></i> Notificaciones por Email</li>
                                        <li class="list-group-item"><i class="fas fa-check text-success"></i> Alertas recurrentes</li>
                                    @else
                                        <li class="list-group-item"><i class="fas fa-check text-success"></i> Alertas ilimitadas</li>
                                        <li class="list-group-item"><i class="fas fa-check text-success"></i> Email + WhatsApp</li>
                                        <li class="list-group-item"><i class="fas fa-check text-success"></i> Alertas recurrentes</li>
                                    @endif
                                </ul>
                            </div>
                        </div>

                        @if($currentSubscription->plan_type != 'pro')
                            <div class="text-center mb-4">
                                <a href="{{ route('user.subscription.upgrade') }}" class="btn btn-primary btn-lg">
                                    <i class="fas fa-arrow-up"></i> Actualizar Plan
                                </a>
                            </div>
                        @endif
                    @else
                        <div class="alert alert-warning">
                            <h5><i class="fas fa-exclamation-triangle"></i> No tienes una suscripción activa</h5>
                            <p>Actualiza tu plan para disfrutar de más beneficios.</p>
                            <a href="{{ route('user.subscription.upgrade') }}" class="btn btn-primary">
                                <i class="fas fa-arrow-up"></i> Seleccionar Plan
                            </a>
                        </div>
                    @endif

                    <hr>

                    <h5>Historial de Suscripciones</h5>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Plan</th>
                                    <th>Inicio</th>
                                    <th>Fin</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($subscriptionHistory as $sub)
                                    <tr>
                                        <td>
                                            @if($sub->plan_type == 'free')
                                                <span class="badge bg-secondary">Free</span>
                                            @elseif($sub->plan_type == 'basic')
                                                <span class="badge bg-warning">Basic</span>
                                            @else
                                                <span class="badge bg-primary">Pro</span>
                                            @endif
                                        </td>
                                        <td>{{ $sub->starts_at->format('d/m/Y') }}</td>
                                        <td>{{ $sub->ends_at->format('d/m/Y') }}</td>
                                        <td>
                                            @if($sub->isActive())
                                                <span class="badge bg-success">Activo</span>
                                            @else
                                                <span class="badge bg-secondary">Expirado</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center">No hay historial de suscripciones</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
