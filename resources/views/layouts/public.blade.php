<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ config('app.name', 'Utilitary') }} - @yield('title')</title>
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"
          integrity="sha512-iBBXm8fW90+nuLcSKlbmrPcLa0OT92xO1BIsZ+ywDWZCvqsWgccV3gFoRBv0z+8dLJgyAHIhR35VZc2oM/gI1w=="
          crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.1.0/css/adminlte.min.css"
          integrity="sha512-mxrUXSjrxl8vm5GwafxcqTrEwO1/oBNU25l20GODsysHReZo4uhVISzAKzaABH6/tTfAxZrY2FprmeAP5UZY8A=="
          crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css"
          integrity="sha384-TX8t27EcRE3e/ihU7zmQxVncDAy5uIKz4rEkgIXeMed4M0jlfIDPvg6uqKI2xXr2"
          crossorigin="anonymous">
    <link rel="stylesheet" href="{{ asset('css/loader.css') }}">
    @stack('third_party_stylesheets')
    @stack('page_css')
<style>
[data-theme="dark"] .modal-content {
    background: #0f172a;
    color: #e2e8f0;
    border-color: rgba(148, 163, 184, 0.2);
}
[data-theme="dark"] .modal-header,
[data-theme="dark"] .modal-footer {
    border-color: rgba(148, 163, 184, 0.16);
}
[data-theme="dark"] .modal-content a {
    color: #93c5fd;
}
[data-theme="dark"] .modal-content .close {
    color: #e2e8f0;
    text-shadow: none;
    opacity: 0.8;
}
[data-theme="dark"] .modal-backdrop.show {
    opacity: 0.75;
}

/* Dark mode for AdminLTE header and sidebar */
[data-theme="dark"] .main-header {
    background: #0f172a !important;
    border-bottom: 1px solid rgba(148, 163, 184, 0.2) !important;
}

[data-theme="dark"] .main-header .navbar-nav .nav-link {
    color: #e2e8f0 !important;
}

[data-theme="dark"] .main-header .navbar-nav .nav-link:hover {
    color: #93c5fd !important;
    background: rgba(96, 165, 250, 0.1) !important;
}

[data-theme="dark"] .main-header .navbar-white {
    background: #0f172a !important;
}

[data-theme="dark"] .main-sidebar {
    background: #1a1f2a !important;
}

[data-theme="dark"] .sidebar-dark-primary {
    background: #1a1f2a !important;
}

[data-theme="dark"] .sidebar-dark-primary .nav-sidebar .nav-link {
    color: #cbd5f5 !important;
}

[data-theme="dark"] .sidebar-dark-primary .nav-sidebar .nav-link:hover {
    background: rgba(96, 165, 250, 0.1) !important;
    color: #93c5fd !important;
}

[data-theme="dark"] .sidebar-dark-primary .nav-sidebar .nav-link.active {
    background: #3b82f6 !important;
    color: #fff !important;
}

[data-theme="dark"] .brand-link {
    background: #1a1f2a !important;
    border-bottom: 1px solid rgba(148, 163, 184, 0.2) !important;
}

[data-theme="dark"] .brand-text {
    color: #e2e8f0 !important;
}

[data-theme="dark"] .nav-header {
    color: #94a3b8 !important;
    background: rgba(0, 0, 0, 0.1) !important;
}

[data-theme="dark"] .main-footer {
    background: #1a1f2a !important;
    color: #cbd5f5 !important;
    border-top: 1px solid rgba(148, 163, 184, 0.2) !important;
}

[data-theme="dark"] .main-footer a {
    color: #93c5fd !important;
}

[data-theme="dark"] .dropdown-menu {
    background: #0f172a !important;
    border: 1px solid rgba(148, 163, 184, 0.2) !important;
}

[data-theme="dark"] .dropdown-item {
    color: #e2e8f0 !important;
}

[data-theme="dark"] .dropdown-item:hover {
    background: rgba(96, 165, 250, 0.1) !important;
    color: #93c5fd !important;
}

[data-theme="dark"] .user-header {
    background: #3b82f6 !important;
}

[data-theme="dark"] .user-footer {
    background: #111827 !important;
    border-top: 1px solid rgba(148, 163, 184, 0.2) !important;
}
</style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
@php
    $guestConsentAccepted = true;
    if (!Auth::check()) {
        $guestService = app(\App\Modules\Core\Services\GuestService::class);
        $guestId = $guestService->getGuestId();
        $guestConsentAccepted = \App\Models\GuestConsent::where('guest_id', $guestId)->exists();
    }
@endphp
<div class="wrapper">
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            @auth
                @if(Auth::user()->is_admin)
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('home') }}">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                @endif
            @endauth
        </ul>

        <ul class="navbar-nav ml-auto align-items-center">
            @auth
                <li class="nav-item dropdown user-menu">
                    <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">
                        <img src="https://assets.infyom.com/logo/blue_logo_150x150.png"
                             class="user-image img-circle elevation-2" alt="User Image">
                        <span class="d-none d-md-inline">{{ Auth::user()->name }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                        <li class="user-header bg-primary">
                            <img src="https://assets.infyom.com/logo/blue_logo_150x150.png"
                                 class="img-circle elevation-2"
                                 alt="User Image">
                            <p>
                                {{ Auth::user()->name }}
                                <small>Miembro desde {{ Auth::user()->created_at->format('M. Y') }}</small>
                            </p>
                        </li>
                        <li class="user-footer">
                            @if(Auth::user()->is_admin)
                                <a href="{{ route('home') }}" class="btn btn-default btn-flat">Dashboard</a>
                            @endif
                            <a href="#" class="btn btn-default btn-flat float-right"
                               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                Cerrar sesión
                            </a>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                @csrf
                            </form>
                        </li>
                    </ul>
                </li>
            @else
                <li class="nav-item">
                    <a href="{{ route('login') }}" class="nav-link">Iniciar sesión</a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('register') }}" class="nav-link">Registrarse</a>
                </li>
            @endauth
        </ul>
    </nav>

    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="{{ url('/') }}" class="brand-link">
            <img src="https://assets.infyom.com/logo/blue_logo_150x150.png"
                 alt="Utilitary Logo"
                 class="brand-image img-circle elevation-3">
            <span class="brand-text font-weight-light">Utilitary</span>
        </a>

        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    <li class="nav-header">Utilitarios</li>
                    <li class="nav-item">
                        <a href="{{ route('currency-alert.index') }}" class="nav-link {{ request()->routeIs('currency-alert.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-bell"></i>
                            <p>Alertas de divisa</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('offer-alerts.index') }}" class="nav-link {{ request()->routeIs('offer-alerts.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-tag"></i>
                            <p>Alertas de ofertas</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('supermarket-comparator.index') }}" class="nav-link {{ request()->routeIs('supermarket-comparator.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-shopping-basket"></i>
                            <p>Comparador supermercados</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('name-raffle.index') }}" class="nav-link {{ request()->routeIs('name-raffle.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-random"></i>
                            <p>Sorteo de nombres</p>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>

    <div class="content-wrapper">
        @yield('content')
    </div>

    <footer class="main-footer">
        <div class="float-right d-none d-sm-block">
            <b>Version</b> 3.1.0
        </div>
        <strong>&copy; {{ date('Y') }} Utilitary.</strong> Todos los derechos reservados.
        <span class="ml-2">
            <a href="{{ route('legal.privacy') }}">Política de privacidad</a> ·
            <a href="{{ route('legal.terms') }}">Términos y condiciones</a>
        </span>
    </footer>
</div>

<div class="fx-loader-overlay" id="global-loader" aria-live="polite" aria-busy="true">
    <div class="fx-loader-spinner" aria-label="Cargando"></div>
</div>

<div class="modal fade" id="guest-consent-modal" tabindex="-1" role="dialog" aria-labelledby="guest-consent-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="guest-consent-title">Antes de continuar</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Para usar el aplicativo debes aceptar los siguientes documentos:</p>
                <ul class="mb-3">
                    <li><a href="{{ route('legal.terms') }}" target="_blank" rel="noopener">Términos y condiciones</a></li>
                    <li><a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener">Política de privacidad</a></li>
                </ul>
                <small class="text-muted">Tu aceptación se guardará para este navegador.</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="guest-consent-accept">Aceptar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"
        integrity="sha512-bLT0Qm9VnAYZDflyKcBaQ2gg0hSYNQrJ8RilYldYQ1FxQYoCLtUjuuRuZo+fjqhx/qtq/1itJ0C2ejDxltZVFg=="
        crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"
        integrity="sha384-9/reFTGAW83EW2RDu2S0VKaIzap3H66lZH81PoYlFhbGU+6BZp6G7niu735Sk7lN"
        crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.min.js"
        integrity="sha384-w1Q4orYjBQndcko6MimVbzY0tgp4pWB4lZ7lr30WKz0vr/aWKhXdBNmNb5D92v7s"
        crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.1.0/js/adminlte.min.js"
        integrity="sha512-AJUWwfMxFuQLv1iPZOTZX0N/jTCIrLxyZjTRKQostNU71MzZTEPHjajSK20Kj1TwJELpP7gl+ShXw5brpnKwEg=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="{{ asset('js/loader.js') }}"></script>
<script>
    window.guestConsentState = {
        required: @json(!Auth::check()),
        accepted: @json($guestConsentAccepted),
        acceptUrl: "{{ route('guest.consent.store') }}",
    };
</script>
<script>
    (function () {
        const state = window.guestConsentState || { required: false, accepted: true };
        const modal = document.getElementById('guest-consent-modal');
        const acceptBtn = document.getElementById('guest-consent-accept');
        let pendingAction = null;

        const showModal = () => {
            if (window.jQuery && modal) {
                window.jQuery(modal).modal({
                    backdrop: 'static',
                    keyboard: false,
                });
            }
        };

        window.ensureGuestConsent = (callback) => {
            if (!state.required || state.accepted) {
                return true;
            }
            if (typeof callback === 'function') {
                pendingAction = callback;
            }
            showModal();
            return false;
        };

        document.addEventListener('submit', function (e) {
            const form = e.target;
            if (!form || !form.matches('[data-guest-consent="required"]')) return;
            if (window.ensureGuestConsent(() => {
                if (form.requestSubmit) {
                    form.requestSubmit();
                } else {
                    form.submit();
                }
            })) {
                return;
            }
            e.preventDefault();
            e.stopImmediatePropagation();
        }, true);

        if (acceptBtn) {
            acceptBtn.addEventListener('click', async function () {
                if (state.accepted) {
                    if (pendingAction) pendingAction();
                    return;
                }
                acceptBtn.disabled = true;
                try {
                    const res = await fetch(state.acceptUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        },
                    });
                    if (!res.ok) throw new Error('No se pudo registrar la aceptación.');
                    state.accepted = true;
                    if (window.jQuery && modal) {
                        window.jQuery(modal).modal('hide');
                    }
                    if (pendingAction) pendingAction();
                } catch (err) {
                    console.error(err);
                    alert('No se pudo registrar la aceptación. Inténtalo nuevamente.');
                } finally {
                    acceptBtn.disabled = false;
                    pendingAction = null;
                }
            });
        }
    })();
</script>

@stack('third_party_scripts')
@stack('page_scripts')
</body>
</html>
