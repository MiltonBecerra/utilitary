<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return redirect()->route('login')->withErrors(['msg' => 'Debes iniciar sesion para acceder.']);
        }

        if (!auth()->user()->is_admin) {
            return redirect()
                ->route('currency-alert.index')
                ->withErrors(['msg' => 'No tienes permisos para acceder al dashboard de administracion.']);
        }

        return $next($request);
    }
}

