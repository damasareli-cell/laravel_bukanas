<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Acceso extends Model {
    protected $table = 'dts_acceso';
    public $timestamps = false;
    protected $fillable = ['correo', 'contrasena_hash', 'rol', 'estado', 'dni'];
}