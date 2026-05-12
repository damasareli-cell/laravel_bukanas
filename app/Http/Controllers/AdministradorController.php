<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Repartidor;

class AdministradorController extends Controller {

    public function registrarPersonal(Request $request) {
        try {
            DB::beginTransaction();

            // 1. Guardar en Persona
            DB::table('persona')->insert([
                'dni' => $request->dni,
                'P_nombre' => $request->p_nombre,
                'S_nombre' => $request->s_nombre,
                'P_apellido' => $request->p_apellido,
                'S_apellido' => $request->s_apellido,
                'edad' => $request->edad,
                'genero' => $request->genero
            ]);

            // 2. Guardar en Contacto
            DB::table('dts_contacto')->insert([
                'correo' => $request->correo,
                'telefono' => $request->telefono,
                'dni' => $request->dni
            ]);

            // 3. Guardar en Ubicación
            DB::table('dts_ubicacion')->insert([
                'pais' => $request->pais,
                'ciudad' => $request->ciudad,
                'direccion' => $request->direccion,
                
                'dni' => $request->dni
            ]);

            // 3. Si es Repartidor, guardar datos de vehículo
            if ($request->rol == 'repartidor') {
                DB::table('repartidor')->insert([
                    'dni' => $request->dni,
                    'placa_vehiculo' => $request->placa,
                    'tipo_vehiculo' => $request->tipo_vehiculo,
                    'experiencia' => $request->experiencia,
                    'estado_disponibilidad' => 'disponible'
                ]);
            }

            // 4. Crear Acceso con HASH
            DB::table('dts_acceso')->insert([
                'correo' => $request->correo,
                'contrasena_hash' => Hash::make($request->contrasena), // AQUÍ SE ACTIVA EL HASH
                'rol' => $request->rol,
                'estado' => 'activo',
                'dni' => $request->dni
            ]);

            DB::commit();
            return response()->json(['exito' => true, 'mensaje' => 'Personal registrado con hash']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['exito' => false, 'mensaje' => 'Error: ' . $e->getMessage()], 500);
        }
    }
      public function index() {
        return DB::table('persona')
            ->join('dts_acceso', 'persona.dni', '=', 'dts_acceso.dni')
            ->select('persona.dni', 'persona.P_nombre', 'persona.P_apellido', 'dts_acceso.rol', 'dts_acceso.correo', 'dts_acceso.estado')
            ->get();
    }

    public function reporteVentasDia(Request $request) {
        // Si viene una fecha en el request la usamos, sino usamos hoy
        $hoy = $request->query('fecha', date('Y-m-d'));
        
        $ventas_dia = DB::table('pago')
            ->whereDate('fecha', $hoy)
            ->whereIn('estado', ['pagado', 'pendiente'])
            ->sum('monto');
            
        $pedidos_dia = DB::table('pedido')
            ->whereDate('fecha', $hoy)
            ->count();
            
        $detalles_ventas = DB::table('pago')
            ->join('pedido', 'pago.pedido_id', '=', 'pedido.id')
            ->join('persona', 'pedido.cliente_dni', '=', 'persona.dni')
            ->select('pago.id', 'pago.monto', 'pago.metodo', 'pago.fecha', 'pago.estado', 'pedido.id as pedido_id', DB::raw("CONCAT(persona.P_nombre, ' ', persona.P_apellido) as cliente"))
            ->whereDate('pago.fecha', $hoy)
            ->whereIn('pago.estado', ['pagado', 'pendiente'])
            ->get();

        // También aplicamos el filtro a la lista de pedidos si fuera necesario
        // Pero el usuario pidió filtrar el reporte
        
        return response()->json([
            'total_ventas' => (float)$ventas_dia,
            'total_pedidos' => $pedidos_dia,
            'ventas' => $detalles_ventas,
            'fecha_reporte' => $hoy
        ]);
    }

    public function listarPedidos(Request $request) {
        $fecha = $request->query('fecha'); // Filtro opcional

        $query = DB::table('pedido')
            ->join('persona', 'pedido.cliente_dni', '=', 'persona.dni')
            ->select('pedido.*', DB::raw("CONCAT(persona.P_nombre, ' ', persona.P_apellido) as cliente_nombre"))
            ->orderBy('pedido.fecha', 'desc');

        if ($fecha) {
            $query->whereDate('pedido.fecha', $fecha);
        }

        $pedidos = $query->get();

        foreach ($pedidos as $pedido) {
            $detalles = DB::table('detalle_pedido')
                ->join('producto', 'detalle_pedido.producto_id', '=', 'producto.id')
                ->where('pedido_id', $pedido->id)
                ->select(DB::raw("CONCAT(detalle_pedido.cantidad, 'x ', producto.nombre) as info"))
                ->pluck('info');
            
            $pedido->productos_nombres = $detalles->implode(', ');
        }

        return $pedidos;
    }

    public function productosMasVendidos() {
        return DB::table('detalle_pedido')
            ->join('producto', 'detalle_pedido.producto_id', '=', 'producto.id')
            ->select('producto.nombre', DB::raw("SUM(detalle_pedido.cantidad) as total_vendido"))
            ->groupBy('producto.id', 'producto.nombre')
            ->orderBy('total_vendido', "desc")
            ->limit(5)
            ->get();
    }

    public function alertarStockBajo() {
        return DB::table('producto')
            ->where('stock', '<=', 5)
            ->select('id', 'nombre', 'stock', "imagen")
            ->get();
    }
}