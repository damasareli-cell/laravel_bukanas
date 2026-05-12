<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ConfiguracionController extends Controller
{
    // Método para obtener el estado actual
    public function obtenerAcceso()
    {
        // Ejemplo usando archivos (puedes usar Base de Datos si prefieres)
        $config = Storage::exists('app_config.json') 
            ? json_decode(Storage::get('app_config.json'), true) 
            : ['app_activa' => true, 'mensaje_bloqueo' => '', 'dias_vacaciones' => []];

        // Aseguramos que dias_vacaciones siempre exista
        if (!isset($config['dias_vacaciones'])) {
            $config['dias_vacaciones'] = [];
        }

        return response()->json($config);
    }

    // Método para guardar el estado
    public function actualizarAcceso(Request $request)
    {
        $data = [
            'app_activa' => $request->app_activa,
            'mensaje_bloqueo' => $request->mensaje_bloqueo,
            'dias_vacaciones' => $request->dias_vacaciones ?? []
        ];

        Storage::put('app_config.json', json_encode($data));

        return response()->json([
            'exito' => true,
            'mensaje' => 'Configuración actualizada correctamente'
        ]);
    }
}

?>