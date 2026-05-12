<?php
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\AutenticacionController;
use App\Http\Controllers\AdministradorController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\FacturaController;
use App\Http\Controllers\ReservaController;
use App\Http\Controllers\PagoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
// CRUD de Usuarios
Route::post('/login', [AutenticacionController::class, 'login']);
Route::post('/registro', [AutenticacionController::class, 'registro']);   

// CRUD de Categorías
Route::get('/categorias', [CategoriaController::class, 'index']);
Route::post('/categorias', [CategoriaController::class, 'store']);
Route::put('/categorias/{id}', [CategoriaController::class, 'update']);
Route::delete('/categorias/{id}', [CategoriaController::class, 'destroy']);

// CRUD de Productos
Route::get('/productos', [ProductoController::class, 'index']);
Route::post('/productos', [ProductoController::class, 'store']);
Route::put('/productos/{id}', [ProductoController::class, 'update']);
Route::delete('/productos/{id}', [ProductoController::class, 'destroy']);


// CRUD de Personal (Administrador)
Route::post('/personal', [AdministradorController::class, 'registrarPersonal']);

Route::post('/registrar-personal', [AdministradorController::class, 'registrarPersonal']);

//para obtener el perfil del usuario
Route::get('/perfil/{dni}', [AutenticacionController::class, 'obtenerPerfil']);   


// --- RUTAS PARA EL CLIENTE ---
Route::post('/pedidos', [PedidoController::class, 'store']); // Guardar pedido nuevo
Route::post('/pagos/stripe/intent', [PagoController::class, 'crearPaymentIntent']); // Crear intento de pago Stripe

// --- RUTAS PARA EL RECEPCIONISTA ---
Route::get('/pedidos/pendientes', [PedidoController::class, 'pendientes']);
Route::get('/repartidores/disponibles', [PedidoController::class, 'repartidoresDisponibles']);
Route::post('/pedidos/asignar', [PedidoController::class, 'asignar']);
Route::post('/pedidos/actualizar-estado', [PedidoController::class, 'actualizarEstadoManual']);
Route::get('/cliente/{dni}/notificaciones', [PedidoController::class, 'notificaciones']);
Route::post('/notificaciones/marcar-leida', [PedidoController::class, 'marcarNotificacionLeida']);

// --- RUTAS PARA EL REPARTIDOR ---
// IMPORTANTE: Asegúrate de que el {dni} se reciba correctamente
Route::get('/repartidor/{dni}/pedidos-asignados', [PedidoController::class, 'pedidosAsignados']);
Route::get('/repartidor/{dni}/historial-entregas', [PedidoController::class, 'historialEntregas']);
Route::post('/pedidos/entregado', [PedidoController::class, 'marcarEntregado']);

//rutas para la reservación
Route::post('/reservas', [ReservaController::class, 'store']); // Guardar


// Rutas de Facturación
Route::get('cliente/{dni}/facturas', [FacturaController::class, 'index']);
Route::get('factura/{id}', [FacturaController::class, 'show']);

//crud para los productos
// Gestión de Productos
Route::get('productos', [ProductoController::class, 'index']);
Route::post('productos', [ProductoController::class, 'store']);
Route::post('productos/actualizar/{id}', [ProductoController::class, 'update']); // Coincide con tu ApiService


    Route::get('usuarios', [AdministradorController::class, 'index']); // Obtener todos
    Route::delete('usuarios/{dni}', [AdministradorController::class, 'destroy']); // Eliminar

    //rutas para recuperar contraseña
    Route::post('/recuperar-password', [AutenticacionController::class, 'recuperarPassword']);
    Route::post('/cambiar-password', [AutenticacionController::class, 'cambiarPassword']);  
    Route::post('password/enviar-token', [AutenticacionController::class, 'enviarToken']);
    Route::get('/perfil/{dni}', [AutenticacionController::class, 'obtenerPerfil']);



    Route::post('password/enviar-otp', [AutenticacionController::class, 'enviarOtp']);
Route::post('password/validar-otp', [AutenticacionController::class, 'validarOtp']);
Route::post('password/reset-con-otp', [AutenticacionController::class, 'resetConOtp']);

// Rutas para PayPal Callbacks
Route::get('paypal/success', [PedidoController::class, 'paypalSuccess'])->name('paypal.success');
Route::get('paypal/cancel', [PedidoController::class, 'paypalCancel'])->name('paypal.cancel');

    // Rutas para los callbacks de PayPal
Route::get('paypal/success', [PedidoController::class, 'paypalSuccess'])->name('paypal.success');
Route::get('paypal/cancel', [PedidoController::class, 'paypalCancel'])->name('paypal.cancel');
Route::get('paypal/cancel', [PedidoController::class, 'paypalCancel'])->name('paypal.cancel');
// routes/api.php

Route::get('admin/configuracion-acceso', [App\Http\Controllers\ConfiguracionController::class, 'obtenerAcceso']);
Route::post('admin/actualizar-acceso', [App\Http\Controllers\ConfiguracionController::class, 'actualizarAcceso']);

// --- RUTAS PARA COMPLEMENTOS (EXTRAS) ---
Route::get('/complementos', [App\Http\Controllers\ComplementoController::class, 'index']); // Cliente/Admin listan
Route::post('/complementos', [App\Http\Controllers\ComplementoController::class, 'store']); // Admin agrega
Route::post('/complementos/{id}', [App\Http\Controllers\ComplementoController::class, 'update']); // Admin edita
Route::delete('/complementos/{id}', [App\Http\Controllers\ComplementoController::class, 'destroy']); // Admin borra

// --- REPORTES PARA ADMIN ---
Route::get('/admin/reporte-ventas-dia', [AdministradorController::class, 'reporteVentasDia']);
Route::get('/admin/pedidos', [AdministradorController::class, 'listarPedidos']);
Route::get('/admin/best-sellers', [AdministradorController::class, 'productosMasVendidos']);
Route::get('/admin/stock-bajo', [AdministradorController::class, 'alertarStockBajo']);
// En routes/api.php
Route::post('categorias/asignar-extras', [App\Http\Controllers\CategoriaController::class, 'asignarExtras']);
Route::post('productos/asignar-extras', [App\Http\Controllers\ProductoController::class, 'asignarExtras']);
