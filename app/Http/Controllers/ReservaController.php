<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Reserva;
use App\Models\DetalleReservaProducto;
class ReservaController extends Controller
{
public function store(Request $request)
{
    try {
        \DB::beginTransaction();

        $reservaId = \DB::table('reserva')->insertGetId([
            'fecha_reserva'     => $request->fecha_reserva,
            'hora_reserva'      => $request->hora_reserva,
            'cantidad_personas' => $request->cantidad_personas,
            'estado'            => 'pendiente',
            'cliente_dni'       => $request->cliente_dni
        ]);

        if ($request->has('detalles')) {
            foreach ($request->detalles as $detalle) {
                \DB::table('detalle_reserva_producto')->insert([
                    'reserva_id'      => $reservaId,
                    'producto_id'     => $detalle['producto_id'],
                    'cantidad'        => $detalle['cantidad'],
                    'precio_unitario' => $detalle['precio_unitario'],
                    'subtotal'        => $detalle['subtotal']
                ]);
            }
        }

        \DB::commit();
        return response()->json(['exito' => true, 'mensaje' => 'Reserva creada']);
    } catch (\Exception $e) {
        \DB::rollBack();
        return response()->json(['exito' => false, 'mensaje' => $e->getMessage()], 500);
    }
}
}