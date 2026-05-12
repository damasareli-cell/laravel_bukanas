<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    protected $table = 'pago';
    public $timestamps = false;

    protected $fillable = [
        'fecha',
        'monto',
        'metodo', // 'efectivo', 'tarjeta', 'transferencia'
        'estado', // 'pendiente', 'pagado', 'rechazado'
        'pedido_id'
    ];

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }
}