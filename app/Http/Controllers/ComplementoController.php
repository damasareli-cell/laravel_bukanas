<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Complemento;
use Illuminate\Support\Facades\Storage;

class ComplementoController extends Controller
{
    // Listar complementos filtrados por categoría Y por producto específico
    public function index(Request $request)
    {
        $idCategoria = $request->query('categoria_id');
        $idProducto = $request->query('producto_id');

        $query = Complemento::where('estado', 'activo');

        if ($idCategoria || $idProducto) {
            $query->where(function($q) use ($idCategoria, $idProducto) {
                if ($idCategoria) {
                    $q->orWhereHas('categorias', function($sub) use ($idCategoria) {
                        $sub->where('categoria.id', $idCategoria);
                    });
                }
                if ($idProducto) {
                    $q->orWhereHas('productos', function($sub) use ($idProducto) {
                        $sub->where('producto.id', $idProducto);
                    });
                }
            });
        }

        $complementos = $query->get();
        foreach ($complementos as $c) {
            if ($c->imagen) {
                // Quitamos prefijos viejos y apuntamos a la nueva carpeta directa en public
                $rutaLimpia = str_replace(['storage/', 'public/'], '', $c->imagen);
                $c->imagen = asset('uploads/' . $rutaLimpia);
            }
        }

        return response()->json($complementos);
    }

    // Guardar nuevo complemento (Admin)
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string',
            'precio' => 'required|numeric'
        ]);

        $complemento = new Complemento($request->all());

        if ($request->hasFile('foto')) {
            $file = $request->file('foto');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/complementos'), $filename);
            $complemento->imagen = 'complementos/' . $filename;
        }

        $complemento->save();

        return response()->json([
            'exito' => true,
            'mensaje' => 'Complemento guardado correctamente',
            'complemento' => $complemento
        ], 201);
    }

    // Actualizar complemento (Admin)
    public function update(Request $request, $id)
    {
        $complemento = Complemento::findOrFail($id);
        $complemento->update($request->all());

        if ($request->hasFile('foto')) {
            $file = $request->file('foto');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/complementos'), $filename);
            $complemento->imagen = 'complementos/' . $filename;
            $complemento->save();
        }

        return response()->json([
            'exito' => true,
            'mensaje' => 'Complemento actualizado correctamente',
            'complemento' => $complemento
        ]);
    }

    // Eliminar o desactivar complemento (Admin)
    public function destroy($id)
    {
        $complemento = Complemento::findOrFail($id);
        $complemento->delete();
        return response()->json(['exito' => true, 'mensaje' => 'Complemento eliminado']);
    }
}
