<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoriaController extends Controller
{
    public function index() {
        $categorias = DB::table('categoria')->get();
        foreach ($categorias as $c) {
            if (isset($c->imagen) && $c->imagen) {
                // Quitamos prefijos viejos y apuntamos a la nueva carpeta directa en public
                $rutaLimpia = str_replace(['storage/', 'public/'], '', $c->imagen);
                $c->imagen = url('uploads/' . $rutaLimpia);
            }
        }
        return response()->json($categorias);
    }

    public function store(Request $request) {
        DB::table('categoria')->insert([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'estado' => 'activa'
        ]);
        return response()->json(['exito' => true, 'mensaje' => 'Categoría guardada']);
    }

    public function update(Request $request, $id) {
        DB::table('categoria')->where('id', $id)->update([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'estado' => $request->estado
        ]);
        return response()->json(['exito' => true, 'mensaje' => 'Categoría actualizada']);
    }

    public function destroy($id) {
        DB::table('categoria')->where('id', $id)->delete();
        return response()->json(['exito' => true, 'mensaje' => 'Categoría eliminada']);
    }

  
// app/Http/Controllers/CategoriaController.php

public function asignarExtras(Request $request) 
{
    // Buscamos la categoría
    $categoria = \App\Models\Categoria::find($request->categoria_id);
    
    // El JSON que enviamos desde Android ["1", "4", "5"]
    $ids = json_decode($request->extras_ids); 
    
    // Guardamos la relación en la tabla categoria_complemento
    $categoria->complementos()->sync($ids);
    
    return response()->json([
        'exito' => true, 
        'mensaje' => 'Adicionales actualizados para esta categoría'
    ]);
}

}