<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Ubicacion extends Model {
    protected $table = 'dts_ubicacion';protected $primaryKey = 'id_ubicacion';
    public $timestamps = false;
    protected $fillable = ['pais', 'ciudad', 'direccion', 'dni'];
}