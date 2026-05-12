<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reserva extends Model
{protected $table = 'reserva';
    public $timestamps = false;

    protected $fillable = [
        'fecha_reserva',
        'hora_reserva',
        'cantidad_personas',
        'estado',
        'cliente_dni'
    ];
}