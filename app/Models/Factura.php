<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Factura extends Model
{
    protected $table = 'factura';
    public $timestamps = false; // Tu tabla usa 'fecha', no 'created_at'

    protected $fillable = [
        'fecha',
        'subtotal',
        'impuesto',
        'total',
        'pedido_id'
    ];

    // Relación con los detalles
    public function detalles()
    {
        return $this->hasMany(DetalleFactura::class, 'factura_id');
    }

    // Relación con el pedido
    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }
}