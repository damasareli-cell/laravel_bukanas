<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Repartidor extends Model {
    protected $table = 'repartidor';
    protected $primaryKey = 'id'; // O el que tengas en tu BD
    public $timestamps = false;
    protected $fillable = ['dni', 'placa_vehiculo', 'tipo_vehiculo', 'experiencia', 'estado_disponibilidad'];
}