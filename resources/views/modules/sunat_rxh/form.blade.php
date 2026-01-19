@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="m-0 text-dark">Emitir Recibo por Honorarios Electrónico</h1>
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Formulario de Emisión</h3>
                </div>
                <form action="{{ route('sunat.rxh.store') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <!-- Login Section -->
                        <h4 class="text-primary"><i class="fas fa-key"></i> Credenciales SUNAT</h4>
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label for="login_ruc">RUC / DNI (Emisor)</label>
                                <input type="text" class="form-control" name="login_ruc" id="login_ruc" placeholder="Ingrese RUC o DNI" required>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="login_user">Usuario SOL</label>
                                <input type="text" class="form-control" name="login_user" id="login_user" placeholder="Ingrese Usuario SOL" required>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="login_password">Clave SOL</label>
                                <input type="password" class="form-control" name="login_password" id="login_password" placeholder="Ingrese Clave SOL" required>
                            </div>
                        </div>
                        <hr>

                        <!-- Client Section -->
                        <h4 class="text-primary"><i class="fas fa-user-tie"></i> Datos del Usuario (Cliente)</h4>
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label>Tipo Documento</label>
                                <select class="form-control" name="cliente_tipo_doc">
                                    <option value="RUC">RUC</option>
                                    <option value="DNI">DNI</option>
                                </select>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="cliente_num_doc">Número Documento</label>
                                <input type="text" class="form-control" name="cliente_num_doc" id="cliente_num_doc" placeholder="DNI o RUC del Cliente" required>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="cliente_razon_social">Razón Social / Nombre (Opcional si valida)</label>
                                <input type="text" class="form-control" name="cliente_razon_social" id="cliente_razon_social" placeholder="Nombre del cliente">
                            </div>
                        </div>

                        <div class="row">
                            <div class="form-group col-md-4">
                                <label for="email">Email para envio</label>
                                <input type="text" class="form-control" name="email" id="email" placeholder="correo@dominio.com" required>
                            </div>
                        </div>

                        <!-- Service Details -->
                        <h4 class="text-primary"><i class="fas fa-file-invoice-dollar"></i> Datos del Servicio</h4>
                        <div class="row">
                             <div class="form-group col-md-12">
                                <label for="descripcion">Descripción del Servicio</label>
                                <textarea class="form-control" name="descripcion" id="descripcion" rows="2" placeholder="Describa el servicio prestado..." required></textarea>
                            </div>
                        </div>
                        <div class="row">
                             <div class="form-group col-md-12">
                                <label for="observacion">Observación (Opcional)</label>
                                <input type="text" class="form-control" name="observacion" id="observacion">
                            </div>
                        </div>

                        <div class="row">
                            <div class="form-group col-md-3">
                                <label for="fecha_emision">Fecha de Emisión</label>
                                <input type="date" class="form-control" name="fecha_emision" id="fecha_emision" value="{{ date('Y-m-d') }}" required>
                            </div>
                             <div class="form-group col-md-3">
                                <label>Tipo de Renta (Inciso A/B)</label>
                                <select class="form-control" name="tipo_renta">
                                    <option value="A">Inciso A (Profesión, Arte, Ciencia)</option>
                                    <option value="B">Inciso B (Director, Síndico, etc.)</option>
                                </select>
                            </div>
                            <div class="form-group col-md-3">
                                <label>Retención (8%)</label>
                                <select class="form-control" name="retencion">
                                    <option value="NO">No</option>
                                    <option value="SI">Sí</option>
                                </select>
                            </div>
                            <div class="form-group col-md-3">
                                <label>Medio de Pago</label>
                                <select class="form-control" name="medio_pago">
                                    <option value="001">Depósito en Cuenta</option>
                                    <option value="009">Efectivo (General)</option>
                                    <option value="003">Transferencia de Fondos</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label>Es de tercera categoria?</label>
                                <select class="form-control" name="tercera_categoria" id="tercera_categoria">
                                    <option value="0" selected>No</option>
                                    <option value="1">Si</option>
                                </select>
                            </div>
                            <div class="form-group col-md-8" id="deduccion_wrap" style="display: none;">
                                <label>Sustentara como gasto este servicio?</label>
                                <div>
                                    <label class="mr-3">
                                        <input type="radio" name="inddeduccion" value="1" disabled> Si
                                    </label>
                                    <label>
                                        <input type="radio" name="inddeduccion" value="0" checked disabled> No
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="form-group col-md-4">
                                <label>Moneda</label>
                                <select class="form-control" name="moneda">
                                    <option value="PEN">Soles</option>
                                    <option value="USD">Dólares</option>
                                </select>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="monto_total">Monto Total</label>
                                <input type="number" step="0.01" class="form-control" name="monto_total" id="monto_total" required>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-success"><i class="fas fa-paper-plane"></i> Emitir Recibo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    (function () {
        const select = document.getElementById('tercera_categoria');
        const wrap = document.getElementById('deduccion_wrap');
        const radios = wrap ? wrap.querySelectorAll('input[name="inddeduccion"]') : [];

        const sync = () => {
            const enabled = select && select.value === '1';
            if (wrap) {
                wrap.style.display = enabled ? '' : 'none';
            }
            radios.forEach((radio) => {
                radio.disabled = !enabled;
                if (!enabled) {
                    radio.checked = radio.value === '0';
                }
            });
        };

        if (select) {
            select.addEventListener('change', sync);
            sync();
        }
    })();
</script>
@endsection
