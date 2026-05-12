<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $table = 'categoria'; // Nombre real de tu tabla
    public $timestamps = false;    // Como tu tabla no tiene created_at/updated_at

    protected $fillable = [
        'nombre',
        'descripcion',
        'estado'
    ];

    // app/Models/Categoria.php
public function complementos()
{
    return $this->belongsToMany(Complemento::class, 'categoria_complemento', 'categoria_id', 'complemento_id');
}


    // Relación: Una categoría tiene muchos productos
    public function productos()
    {
        return $this->hasMany(Producto::class, 'categoria_id', 'id');
    }
}