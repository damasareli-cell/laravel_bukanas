<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Complemento extends Model
{
    protected $table = 'complemento';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'precio',
        'imagen',
        'stock',
        'estado'
    ];

    public function categorias()
    {
        return $this->belongsToMany(Categoria::class, 'categoria_complemento', 'complemento_id', 'categoria_id');
    }

    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'producto_complemento', 'complemento_id', 'producto_id');
    }
}
