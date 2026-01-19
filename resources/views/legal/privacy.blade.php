@extends('layouts.public')

@section('title', 'Política de privacidad')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <h1 class="m-0">Política de privacidad</h1>
        <p class="text-muted mb-0">Última actualización: {{ date('d/m/Y') }}</p>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <p>En Utilitary valoramos tu privacidad. Esta política describe cómo recopilamos, usamos y protegemos tu información.</p>

                <h5>1. Alcance</h5>
                <p>Aplica a los usuarios que acceden al Servicio, ya sea registrados o en modo invitado.</p>

                <h5>2. Información que recopilamos</h5>
                <ul>
                    <li>Datos de cuenta (nombre, correo) cuando te registras.</li>
                    <li>Datos operativos de los utilitarios (alertas, configuración, resultados y preferencias).</li>
                    <li>Datos técnicos mínimos para seguridad, auditoría y funcionamiento.</li>
                </ul>

                <h5>3. Finalidades de uso</h5>
                <ul>
                    <li>Proveer, mantener y mejorar nuestros servicios.</li>
                    <li>Enviar notificaciones relacionadas a tus utilitarios.</li>
                    <li>Prevenir fraudes, abuso y mantener la seguridad.</li>
                </ul>

                <h5>4. Base legal</h5>
                <p>Tratamos tus datos para ejecutar el servicio solicitado y cumplir obligaciones legales.</p>

                <h5>5. Cookies y almacenamiento local</h5>
                <p>Usamos cookies esenciales para la sesión y seguridad. Algunos datos pueden guardarse en tu navegador para mejorar la experiencia.</p>

                <h5>6. Compartición de datos</h5>
                <p>No compartimos tus datos con terceros salvo obligación legal o para proveer el servicio mediante proveedores.</p>

                <h5>7. Conservación</h5>
                <p>Conservamos los datos por el tiempo necesario para brindar el servicio o cumplir obligaciones legales.</p>

                <h5>8. Seguridad</h5>
                <p>Aplicamos medidas razonables para proteger tu información frente a accesos no autorizados.</p>

                <h5>9. Derechos de los titulares</h5>
                <p>Puedes solicitar acceso, actualización o eliminación de tu información contactándonos.</p>

                <h5>10. Cambios en esta política</h5>
                <p>Podemos actualizar esta política. La versión vigente estará disponible en esta página.</p>

                <h5>11. Contacto</h5>
                <p>Para consultas sobre privacidad, escríbenos a soporte@utilitary.com.</p>
            </div>
        </div>
    </div>
</section>
@endsection
