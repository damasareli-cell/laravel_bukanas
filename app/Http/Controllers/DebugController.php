<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;

class DebugController extends Controller {
    public function debugVentas() {
        $hoy = date('Y-m-d');
        $pagos_hoy = DB::table('pago')->whereDate('fecha', $hoy)->get();
        $todos_pagos = DB::table('pago')->limit(5)->get();
        return response()->json([
            'hoy_php' => $hoy,
            'pagos_count_hoy' => count($pagos_hoy),
            'pagos_hoy' => $pagos_hoy,
            'ejemplos_pagos' => $todos_pagos
        ]);
    }
}
