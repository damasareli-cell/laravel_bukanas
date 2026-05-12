<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Persona extends Model {
    protected $table = 'persona';
    protected $primaryKey = 'dni';
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = ['dni', 'P_nombre', 'S_nombre', 'P_apellido', 'S_apellido', 'edad', 'genero'];
}