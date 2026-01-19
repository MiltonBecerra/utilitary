@extends('layouts.public')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-arrow-up"></i> Actualizar Plan</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-4">
                        <strong>Plan Actual:</strong> 
                        @if($currentPlan == 'free')
                            <span class="badge bg-secondary">Free</span>
                        @elseif($currentPlan == 'basic')
                            <span class="badge bg-warning">Basic</span>
                        @else
                            <span class="badge bg-primary">Pro</span>
                        @endif
                    </div>

                    <form action="{{ route('user.subscription.process') }}" method="POST">
                        @csrf
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card h-100 border-warning">
                                    <div class="card-header text-center bg-warning">
                                        <h5>Plan Basic</h5>
                                        <h3>S/ 9.90/mes</h3>
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success"></i> Hasta 5 alertas activas</li>
                                            <li><i class="fas fa-check text-success"></i> Notificaciones por Email</li>
                                            <li><i class="fas fa-check text-success"></i> Alertas recurrentes</li>
                                        </ul>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="plan_type" id="basic" value="basic" required>
                                            <label class="form-check-label" for="basic">
                                                Seleccionar Basic
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card h-100 border-primary shadow">
                                    <div class="card-header text-center bg-primary text-white">
                                        <h5><i class="fas fa-star"></i> Plan Pro</h5>
                                        <h3>S/ 19.90/mes</h3>
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success"></i> Alertas ilimitadas</li>
                                            <li><i class="fas fa-check text-success"></i> Email + WhatsApp</li>
                                            <li><i class="fas fa-check text-success"></i> Alertas recurrentes</li>
                                        </ul>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="plan_type" id="pro" value="pro" required>
                                            <label class="form-check-label" for="pro">
                                                Seleccionar Pro
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="duration_months" class="form-label">Duración de la Suscripción</label>
                            <select name="duration_months" id="duration_months" class="form-select" required>
                                <option value="1">1 mes</option>
                                <option value="3">3 meses (Ahorra 10%)</option>
                                <option value="6">6 meses (Ahorra 15%)</option>
                                <option value="12">12 meses (Ahorra 20%)</option>
                            </select>
                            <small class="text-muted">Selecciona la duración de tu suscripción</small>
                        </div>

                        <div class="alert alert-warning">
                            <strong><i class="fas fa-info-circle"></i> Nota:</strong> Esta es una simulación. En producción, aquí se integraría un sistema de pagos como Stripe, PayPal, o Mercado Pago.
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-credit-card"></i> Procesar Actualización
                            </button>
                            <a href="{{ route('user.subscription') }}" class="btn btn-outline-secondary">
                                Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
