<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $table = 'producto';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'descripcion',
        'precio',
        'stock',
        'imagen',
        'categoria_id'
    ];

    // Relación: Un producto tiene muchos complementos ( extras)
    public function complementos()
    {
        return $this->belongsToMany(Complemento::class, 'producto_complemento', 'producto_id', 'complemento_id');
    }

    // Relación: Un producto pertenece a una categoría
    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id', 'id');
    }
}