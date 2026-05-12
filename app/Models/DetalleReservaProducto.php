<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleReservaProducto extends Model
{
    protected $table = 'detalle_reserva_producto';
    public $timestamps = false;

    protected $fillable = [
        'reserva_id',
        'producto_id',
        'cantidad',
        'precio_unitario',
        'subtotal'
    ];
}