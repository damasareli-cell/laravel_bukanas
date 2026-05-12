<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsignacionPedido extends Model
{
    protected $table = 'asignacion_pedido';
    public $timestamps = false; // Tu tabla usa fecha_asignacion manual

    protected $fillable = [
        'pedido_id',
        'repartidor_id',
        'fecha_asignacion'
    ];

    // Relación: Una asignación pertenece a un pedido
    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    // Relación: Una asignación pertenece a un repartidor
    public function repartidor()
    {
        return $this->belongsTo(Repartidor::class, 'repartidor_id');
    }
}