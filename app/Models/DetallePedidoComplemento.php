<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetallePedidoComplemento extends Model
{
    protected $table = 'detalle_pedido_complemento';
    public $timestamps = false;

    protected $fillable = [
        'detalle_pedido_id',
        'complemento_id',
        'cantidad',
        'precio_unitario',
        'subtotal'
    ];

    public function complemento()
    {
        return $this->belongsTo(Complemento::class, 'complemento_id', 'id');
    }
}
