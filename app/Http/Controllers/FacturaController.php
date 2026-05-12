<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Factura;
use App\Models\DetalleFactura;
class FacturaController extends Controller {

public function index($dni) {
    return DB::table('factura')
        ->join('pedido', 'factura.pedido_id', '=', 'pedido.id')
        ->where('pedido.cliente_dni', $dni)
        ->select('factura.*')
        ->orderBy('factura.fecha', 'desc')
        ->get();
}

public function show($id) {
    $factura = DB::table('factura')->where('id', $id)->first();
    $factura->detalles = DB::table('detalle_factura')
        ->join('producto', 'detalle_factura.producto_id', '=', 'producto.id')
        ->where('factura_id', $id)
        ->select('detalle_factura.*', 'producto.nombre as producto_nombre')
        ->get();
    return response()->json($factura);
}

}