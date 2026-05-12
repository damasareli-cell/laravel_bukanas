<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class PedidoController extends Controller
{
    // Método para que el CLIENTE cree un nuevo pedido con Pago y Factura
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            \Log::info("Iniciando creación de pedido para DNI: " . $request->cliente_dni);

            $estadoPago = 'pendiente';
            $paypalLink = null;

            // --- 1. CREAR EL PEDIDO PRIMERO ---
            $pedidoId = DB::table('pedido')->insertGetId([
                'cliente_dni'       => $request->cliente_dni,
                'direccion_entrega' => $request->direccion_entrega,
                'total'             => $request->total,
                'metodo_pago'       => $request->metodo_pago,
                'estado'            => 'pendiente',
                'fecha'             => now()
            ]);
            \Log::info("Pedido creado con ID: " . $pedidoId);

            // --- 2. LÓGICA DE PAGO CON PAYPAL (Opcional según flujo) ---
            if ($request->metodo_pago == 'tarjeta') {
                $clientId = env('PAYPAL_CLIENT_ID', 'Aerab_E_HqZwOIKqTl6QR7MeunGbgiKSlg611bquFCBGOoeLeZCz2EWDqs81KJiqaHurJVg9wcKPsXc7');
                $secret = env('PAYPAL_SECRET', 'ENUmjlm5qoo14fvxoRNPauByYTx9E78qC55VtyoTU4FsbDlybnp4ghCwAEiGhwCObG4P1ivGnwiL1Onl');

                $responseToken = Http::withOptions(['verify' => false])->asForm()
                    ->withBasicAuth($clientId, $secret)
                    ->post('https://api-m.sandbox.paypal.com/v1/oauth2/token', [
                        'grant_type' => 'client_credentials',
                    ]);

                if ($responseToken->successful()) {
                    $accessToken = $responseToken->json()['access_token'];
                    $totalUSD = number_format($request->total / 24.7, 2, '.', '');
                    $responseOrder = Http::withOptions(['verify' => false])->withToken($accessToken)
                        ->post('https://api-m.sandbox.paypal.com/v2/checkout/orders', [
                            "intent" => "CAPTURE",
                            "purchase_units" => [["amount" => ["currency_code" => "USD", "value" => $totalUSD]]],
                            "application_context" => [
                                "return_url" => route('paypal.success', ['pedido_id' => $pedidoId]),
                                "cancel_url" => route('paypal.cancel')
                            ]
                        ]);

                    if ($responseOrder->successful()) {
                        foreach ($responseOrder->json()['links'] as $link) {
                            if ($link['rel'] === 'approve') {
                                $paypalLink = $link['href'];
                                break;
                            }
                        }
                    }
                }
            }

            // --- 3. DETALLES DEL PEDIDO ---
            foreach ($request->detalles as $item) {
                $detalleId = DB::table('detalle_pedido')->insertGetId([
                    'pedido_id'       => $pedidoId,
                    'producto_id'     => $item['producto_id'],
                    'cantidad'        => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'],
                    'subtotal'        => $item['subtotal']
                ]);

                if (isset($item['complementos']) && is_array($item['complementos'])) {
                    foreach ($item['complementos'] as $extra) {
                        DB::table('detalle_pedido_complemento')->insert([
                            'detalle_pedido_id' => $detalleId,
                            'complemento_id'    => $extra['id'],
                            'cantidad'          => $item['cantidad'],
                            'precio_unitario'   => $extra['precio'],
                            'subtotal'          => $extra['precio'] * $item['cantidad']
                        ]);
                    }
                }
                DB::table('producto')->where('id', $item['producto_id'])->decrement('stock', $item['cantidad']);
            }
            \Log::info("Detalles del pedido guardados correctamente.");

            // --- 4. REGISTRAR PAGO ---
            DB::table('pago')->insert([
                'monto'     => $request->total,
                'metodo'    => $request->metodo_pago,
                'estado'    => $estadoPago,
                'pedido_id' => $pedidoId,
                'fecha'     => now()
            ]);
            \Log::info("Registro de pago creado.");

            // --- 5. GENERAR FACTURA (SOLO SI NO ES EFECTIVO) ---
            $factura = null;
            if ($request->metodo_pago != 'efectivo') {
                $facturaId = DB::table('factura')->insertGetId([
                    'pedido_id' => $pedidoId,
                    'fecha'     => now(),
                    'subtotal'  => $request->total / 1.15,
                    'impuesto'  => $request->total * 0.15,
                    'total'     => $request->total
                ]);

                foreach ($request->detalles as $item) {
                    $detFacturaId = DB::table('detalle_factura')->insertGetId([
                        'factura_id'      => $facturaId,
                        'producto_id'     => $item['producto_id'],
                        'cantidad'        => $item['cantidad'],
                        'precio_unitario' => $item['precio_unitario'],
                        'subtotal'        => $item['subtotal']
                    ]);

                    if (isset($item['complementos']) && is_array($item['complementos'])) {
                        foreach ($item['complementos'] as $extra) {
                            DB::table('detalle_factura_complemento')->insert([
                                'detalle_factura_id' => $detFacturaId,
                                'complemento_id'     => $extra['id'],
                                'precio_unitario'    => $extra['precio'],
                                'subtotal'           => $extra['precio'] * $item['cantidad']
                            ]);
                        }
                    }
                }
                $factura = DB::table('factura')->where('id', $facturaId)->first();
                $factura->cliente_nombre = DB::table('persona')->where('dni', $request->cliente_dni)->value('P_nombre');
                \Log::info("Factura inmediata generada.");
            }

            DB::commit();
            \Log::info("Transmisión confirmada (Commit exitoso).");

            return response()->json([
                'exito' => true,
                'mensaje' => 'Pedido procesado con éxito',
                'factura' => $factura,
                'paypal_link' => $paypalLink
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("ERROR AL GUARDAR PEDIDO: " . $e->getMessage());
            return response()->json(['exito' => false, 'mensaje' => 'Error en base de datos: ' . $e->getMessage()], 500);
        }
    }

    // --- TUS OTRAS FUNCIONES (pendientes, asignar, etc.) ---
    public function pendientes() {
        // Obtener los pedidos pendientes con el nombre del cliente
        $pedidos = DB::table('pedido')
            ->join('persona', 'pedido.cliente_dni', '=', 'persona.dni')
            ->select('pedido.*', DB::raw("CONCAT(persona.P_nombre, ' ', persona.P_apellido) as cliente_nombre"))
            ->where('pedido.estado', 'pendiente')
            ->orderBy('pedido.fecha', 'desc')
            ->get();

        // Para cada pedido, obtener un resumen de los productos solicitados
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

    public function repartidoresDisponibles() {
        // --- LIMPIEZA AUTOMÁTICA (SELF-HEALING) ---
        // Buscamos repartidores que dicen estar "ocupados" pero no tienen ningún pedido en camino.
        $repartidoresOcupados = DB::table('repartidor')->where('estado_disponibilidad', 'ocupado')->get();
        foreach ($repartidoresOcupados as $rep) {
            $tienePedidosActivos = DB::table('asignacion_pedido')
                ->join('pedido', 'asignacion_pedido.pedido_id', '=', 'pedido.id')
                ->where('asignacion_pedido.repartidor_id', $rep->id)
                ->where('pedido.estado', 'en_camino')
                ->exists();

            if (!$tienePedidosActivos) {
                // Si no tiene nada activo, lo liberamos automáticamente
                DB::table('repartidor')->where('id', $rep->id)->update(['estado_disponibilidad' => 'disponible']);
            }
        }

        // Ahora retornamos los que realmente están disponibles
        return DB::table('repartidor')
            ->join('persona', 'repartidor.dni', '=', 'persona.dni')
            ->select('repartidor.*', DB::raw("CONCAT(persona.P_nombre, ' ', persona.P_apellido) as nombre_completo"))
            ->where('repartidor.estado_disponibilidad', 'disponible')
            ->get();
    }

    public function asignar(Request $request) {
        try {
            DB::beginTransaction();

            // 1. Relacionar el pedido con el repartidor
            DB::table('asignacion_pedido')->insert([
                'pedido_id' => $request->pedido_id,
                'repartidor_id' => $request->repartidor_id,
                'fecha_asignacion' => now()
            ]);

            // 2. Cambiar estado del pedido
            DB::table('pedido')->where('id', $request->pedido_id)->update(['estado' => 'en_camino']);

            // 3. Cambiar estado del repartidor a ocupado
            DB::table('repartidor')->where('id', $request->repartidor_id)->update(['estado_disponibilidad' => 'ocupado']);

            // 4. Obtener nombre del repartidor para el mensaje
            $repartidor = DB::table('repartidor')
                ->join('persona', 'repartidor.dni', '=', 'persona.dni')
                ->where('repartidor.id', $request->repartidor_id)
                ->select(DB::raw("CONCAT(persona.P_nombre, ' ', persona.P_apellido) as nombre"))
                ->first();

            DB::commit();

            // 5. Crear notificación para el cliente
            DB::table('notificacion')->insert([
                'cliente_dni' => DB::table('pedido')->where('id', $request->pedido_id)->value('cliente_dni'),
                'titulo' => 'Tu pedido está en camino',
                'mensaje' => "Tu pedido #{$request->pedido_id} ya va en camino con el repartidor {$repartidor->nombre}.",
                'fecha' => now()
            ]);

            return response()->json([
                'exito' => true, 
                'mensaje' => 'Pedido asignado correctamente.',
                'notificacion_cliente' => "Tu pedido #{$request->pedido_id} ya está en camino con el repartidor {$repartidor->nombre}.",
                'repartidor_asignado' => $repartidor->nombre
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['exito' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    public function pedidosAsignados($dni) {
        $repartidor = DB::table('repartidor')->where('dni', $dni)->first();
        if (!$repartidor) return response()->json([]);
        return DB::table('asignacion_pedido')
            ->join('pedido', 'asignacion_pedido.pedido_id', '=', 'pedido.id')
            ->join('persona', 'pedido.cliente_dni', '=', 'persona.dni')
            ->select('pedido.*', DB::raw("CONCAT(persona.P_nombre, ' ', persona.P_apellido) as cliente_nombre"))
            ->where('asignacion_pedido.repartidor_id', $repartidor->id)
            ->where('pedido.estado', 'en_camino')
            ->get();
    }

    public function historialEntregas($dni) {
        return DB::table('asignacion_pedido')
            ->join('pedido', 'asignacion_pedido.pedido_id', '=', 'pedido.id')
            ->join('repartidor', 'asignacion_pedido.repartidor_id', '=', 'repartidor.id')
            ->join('persona', 'pedido.cliente_dni', '=', 'persona.dni')
            ->select('pedido.*', DB::raw("CONCAT(persona.P_nombre, ' ', persona.P_apellido) as cliente_nombre"))
            ->where('repartidor.dni', $dni)
            ->where('pedido.estado', 'entregado')
            ->get();
    }

    public function marcarEntregado(Request $request) {
        try {
            DB::beginTransaction();
            $pedidoId = $request->pedido_id;

            // 1. Marcar pedido como entregado
            DB::table('pedido')->where('id', $pedidoId)->update(['estado' => 'entregado']);

            // 2. Liberar repartidor (si existe asignación)
            $asignacion = DB::table('asignacion_pedido')->where('pedido_id', $pedidoId)->first();
            if ($asignacion) {
                DB::table('repartidor')->where('id', $asignacion->repartidor_id)->update(['estado_disponibilidad' => 'disponible']);
            }
            
            // 3. Crear notificación para el cliente
            $clienteDni = DB::table('pedido')->where('id', $pedidoId)->value('cliente_dni');
            if ($clienteDni) {
                DB::table('notificacion')->insert([
                    'cliente_dni' => $clienteDni,
                    'titulo' => '¡Pedido Entregado!',
                    'mensaje' => "Tu pedido #{$pedidoId} ha sido entregado. ¡Que lo disfrutes!",
                    'fecha' => now()
                ]);
            }

            // 4. Sistema de Puntos
            $puntosGanados = 0;
            $pedido = DB::table('pedido')->where('id', $pedidoId)->first();
            if ($pedido) {
                $puntosGanados = floor($pedido->total / 10);
                DB::table('persona')->where('dni', $pedido->cliente_dni)->increment('puntos', $puntosGanados);
            }

            // 5. Generar Factura si era Efectivo
            if ($pedido && $pedido->metodo_pago == 'efectivo') {
                $facturaId = DB::table('factura')->insertGetId([
                    'pedido_id' => $pedido->id,
                    'fecha'     => now(),
                    'subtotal'  => $pedido->total / 1.15,
                    'impuesto'  => $pedido->total * 0.15,
                    'total'     => $pedido->total
                ]);

                $detalles = DB::table('detalle_pedido')->where('pedido_id', $pedido->id)->get();
                foreach ($detalles as $d) {
                    $detFacturaId = DB::table('detalle_factura')->insertGetId([
                        'factura_id'      => $facturaId,
                        'producto_id'     => $d->producto_id,
                        'cantidad'        => $d->cantidad,
                        'precio_unitario' => $d->precio_unitario,
                        'subtotal'        => $d->subtotal
                    ]);

                    $complementos = DB::table('detalle_pedido_complemento')->where('detalle_pedido_id', $d->id)->get();
                    foreach ($complementos as $c) {
                        DB::table('detalle_factura_complemento')->insert([
                            'detalle_factura_id' => $detFacturaId,
                            'complemento_id'     => $c->complemento_id,
                            'precio_unitario'    => $c->precio_unitario,
                            'subtotal'           => $c->subtotal
                        ]);
                    }
                }
                DB::table('pago')->where('pedido_id', $pedido->id)->update(['estado' => 'pagado']);
            }

            DB::commit();
            return response()->json([
                'exito' => true, 
                'mensaje' => "Entrega confirmada. ¡El cliente ganó {$puntosGanados} BukanaPoints!"
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['exito' => false, 'mensaje' => "Error en servidor: " . $e->getMessage()], 500);
        }
    }

    // --- NUEVOS MÉTODOS PARA NOTIFICACIONES ---
    public function notificaciones($dni) {
        return DB::table('notificacion')
            ->where('cliente_dni', $dni)
            ->orderBy('fecha', 'desc')
            ->limit(30)
            ->get();
    }

    public function marcarNotificacionLeida(Request $request) {
        DB::table('notificacion')
            ->where('id', $request->id)
            ->update(['leido' => 1]);
        return response()->json(['exito' => true]);
    }

    public function actualizarEstadoManual(Request $request) {
        try {
            DB::beginTransaction();
            $pedidoId = $request->pedido_id;
            $nuevoEstado = $request->estado;
            
            DB::table('pedido')->where('id', $pedidoId)->update(['estado' => $nuevoEstado]);
            
            // Crear notificación para el cliente
            DB::table('notificacion')->insert([
                'cliente_dni' => DB::table('pedido')->where('id', $pedidoId)->value('cliente_dni'),
                'titulo' => 'Actualización de Pedido',
                'mensaje' => "El estado de tu pedido #{$pedidoId} ha cambiado a: " . strtoupper($nuevoEstado),
                'fecha' => now()
            ]);
            
            DB::commit();
            return response()->json(['exito' => true, 'mensaje' => 'Estado actualizado y notificación enviada']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['exito' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    public function paypalSuccess(Request $request) {
        $pedidoId = $request->pedido_id;
        $orderId = $request->token; // PayPal envía el Order ID en el parámetro 'token'

        // CAPTURAR EL PAGO EN PAYPAL PARA QUE SE REFLEJE EN EL SANDBOX
        $clientId = 'Aerab_E_HqZwOIKqTl6QR7MeunGbgiKSlg611bquFCBGOoeLeZCz2EWDqs81KJiqaHurJVg9wcKPsXc7';
        $secret = 'ENUmjlm5qoo14fvxoRNPauByYTx9E78qC55VtyoTU4FsbDlybnp4ghCwAEiGhwCObG4P1ivGnwiL1Onl';

        // 1. Obtener Token
        $responseToken = Http::withOptions(['verify' => false])->asForm()
            ->withBasicAuth($clientId, $secret)
            ->post('https://api-m.sandbox.paypal.com/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        if ($responseToken->successful()) {
            $accessToken = $responseToken->json()['access_token'];

            // 2. Ejecutar la captura (v2/checkout/orders/{id}/capture)
            // Forzamos un cuerpo JSON vacío '{}' para cumplir con el esquema estricto de PayPal
            $responseCapture = Http::withOptions(['verify' => false])
                ->withToken($accessToken)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->withBody('{}', 'application/json')
                ->post("https://api-m.sandbox.paypal.com/v2/checkout/orders/{$orderId}/capture");

            if ($responseCapture->successful() || (isset($errorDetail['details'][0]['issue']) && $errorDetail['details'][0]['issue'] === 'ORDER_ALREADY_CAPTURED')) {
                
                // Intentamos actualizar el estado en la base de datos
                $afectadas = DB::table('pago')->where('pedido_id', $pedidoId)->update(['estado' => 'pagado']);
                
                // También actualizamos el estado del pedido a 'pagado' o 'pendiente_preparacion' si lo necesitas
                DB::table('pedido')->where('id', $pedidoId)->update(['estado' => 'pagado']);

                // --- SISTEMA DE PUNTOS (PAYPAL) ---
                $pedido = DB::table('pedido')->where('id', $pedidoId)->first();
                if ($pedido) {
                    $puntosGanados = floor($pedido->total / 10);
                    DB::table('persona')->where('dni', $pedido->cliente_dni)->increment('puntos', $puntosGanados);
                }

                \Log::info("Pago exitoso procesado para Pedido ID: $pedidoId. Filas actualizadas: $afectadas");

                if ($afectadas > 0) {
                    return "¡Pago realizado con éxito! Tu pedido #$pedidoId ya está marcado como PAGADO. Ya puedes regresar a Bukanas App.";
                } else {
                    // Si no se actualizó nada, revisamos si es que ya estaba pagado
                    $pagoActual = DB::table('pago')->where('pedido_id', $pedidoId)->first();
                    if ($pagoActual && $pagoActual->estado === 'pagado') {
                        return "¡Pago confirmado! Este pedido ya estaba marcado como Pagado. Ya puedes regresar a Bukanas App.";
                    }
                    \Log::error("No se encontró el registro de pago para el Pedido ID: $pedidoId en la tabla 'pago'.");
                    return "El pago fue exitoso en PayPal, pero no pudimos encontrar el pedido #$pedidoId en nuestra base de datos para marcarlo como pagado. Por favor, contacta a soporte.";
                }
            }

            // Si llegamos aquí es por otro error no controlado
            $motivo = isset($errorDetail['details'][0]['description']) 
                ? $errorDetail['details'][0]['description'] 
                : ($errorDetail['message'] ?? 'Error desconocido');

            return "Hubo un problema al CONFIRMAR el dinero. <br><b>Motivo:</b> " . $motivo;
        }

        // Registrar error de token para depuración
        \Log::error("Error obteniendo Token de PayPal: " . $responseToken->body());
        return "Hubo un error de comunicación con PayPal al intentar obtener el token de acceso.";
    }

    public function paypalCancel() {
        return "El pago fue cancelado. Intenta de nuevo.";
    }
}