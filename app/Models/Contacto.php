<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Contacto extends Model {
    protected $table = 'dts_contacto';
    protected $primaryKey = 'id_contacto';
    public $timestamps = false;
    protected $fillable = ['correo', 'telefono', 'dni'];
}