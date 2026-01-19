@extends('layouts.public')

@section('title', 'Términos y condiciones')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <h1 class="m-0">Términos y condiciones</h1>
        <p class="text-muted mb-0">Última actualización: {{ date('d/m/Y') }}</p>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <p>Al acceder o usar Utilitary, aceptas estos términos. Si no estás de acuerdo, no utilices el servicio.</p>

                <h5>1. Definiciones</h5>
                <ul>
                    <li><strong>Servicio:</strong> la plataforma Utilitary y sus utilitarios disponibles.</li>
                    <li><strong>Usuario:</strong> cualquier persona que accede al Servicio, registrada o invitada.</li>
                    <li><strong>Contenido:</strong> datos que ingresas o generas dentro del Servicio.</li>
                </ul>

                <h5>2. Elegibilidad y uso permitido</h5>
                <ul>
                    <li>Debes usar el Servicio de forma lícita y conforme a estos términos.</li>
                    <li>No puedes intentar vulnerar la seguridad, sobrecargar o interferir con el Servicio.</li>
                    <li>Estás obligado a respetar las restricciones de tu plan.</li>
                </ul>

                <h5>3. Registro, cuentas y seguridad</h5>
                <ul>
                    <li>Eres responsable de mantener la confidencialidad de tus credenciales.</li>
                    <li>Debes informar cualquier acceso no autorizado a tu cuenta.</li>
                    <li>Podemos suspender cuentas por uso indebido o fraude.</li>
                </ul>

                <h5>4. Planes, pagos y facturación</h5>
                <ul>
                    <li>Los planes de pago se rigen por su descripción y limitaciones vigentes.</li>
                    <li>Los cobros se procesan a través de proveedores externos.</li>
                    <li>Los reembolsos, si aplican, se evaluarán caso por caso.</li>
                </ul>

                <h5>5. Datos y contenido del usuario</h5>
                <ul>
                    <li>Conservas la propiedad de tu Contenido.</li>
                    <li>Nos otorgas una licencia limitada para procesarlo y prestar el Servicio.</li>
                    <li>Eres responsable del contenido que compartes o ingresas.</li>
                </ul>

                <h5>6. Uso de utilitarios y resultados</h5>
                <ul>
                    <li>Las alertas y comparaciones son estimaciones basadas en fuentes externas.</li>
                    <li>No garantizamos exactitud, disponibilidad o resultados específicos.</li>
                    <li>Debes verificar información crítica antes de tomar decisiones.</li>
                </ul>

                <h5>7. Disponibilidad y mantenimiento</h5>
                <p>El Servicio puede suspenderse temporalmente por mantenimiento, mejoras o fallas técnicas.</p>

                <h5>8. Propiedad intelectual</h5>
                <p>El software, marca y diseño del Servicio son propiedad de Utilitary o sus licenciantes.</p>

                <h5>9. Terminación</h5>
                <p>Podemos suspender o terminar el acceso si incumples estos términos o por motivos legales.</p>

                <h5>10. Limitación de responsabilidad</h5>
                <p>En la máxima medida permitida por ley, Utilitary no será responsable por daños indirectos, incidentales o pérdida de datos.</p>

                <h5>11. Cambios en los términos</h5>
                <p>Podemos actualizar estos términos. La versión vigente estará disponible en esta página.</p>

                <h5>12. Legislación aplicable</h5>
                <p>Estos términos se rigen por la legislación aplicable en la jurisdicción del titular del Servicio.</p>

                <h5>13. Contacto</h5>
                <p>Para consultas, escríbenos a soporte@utilitary.com.</p>
            </div>
        </div>
    </div>
</section>
@endsection
