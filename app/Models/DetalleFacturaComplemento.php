<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleFacturaComplemento extends Model
{
    protected $table = 'detalle_factura_complemento';
    public $timestamps = false;

    protected $fillable = [
        'detalle_factura_id',
        'complemento_id',
        'precio_unitario',
        'subtotal'
    ];

    public function complemento()
    {
        return $this->belongsTo(Complemento::class, 'complemento_id', 'id');
    }
}
