<?php

namespace App\Modules\SunatRxH\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\SunatRxH\Services\SunatRxHService;

class SunatRxHController extends Controller
{
    public function index()
    {
        return view('modules.sunat_rxh.form');
    }

    public function store(Request $request)
    {
        $request->validate([
            'login_ruc' => 'required',
            'login_user' => 'required',
            'login_password' => 'required',
            'cliente_num_doc' => 'required',
            'descripcion' => 'required',
            'monto_total' => 'required|numeric',
            'email' => 'required|email',
        ]);

        $service = new SunatRxHService();
        $result = $service->emitirRxH($request->all());

        if ($result['success']) {
            return back()->with('success', 'Recibo emitido correctamente. Mensaje: ' . ($result['message'] ?? 'OK'));
        } else {
            return back()->withErrors(['msg' => 'Error al emitir recibo: ' . ($result['error'] ?? 'Desconocido')]);
        }
    }
}
