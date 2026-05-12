<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use App\Models\Producto;
use App\Models\Categoria;

class ProductoController extends Controller
{
    public function store(Request $request)
    {
        try {
            $producto = new Producto();
            $producto->nombre = $request->nombre;
            $producto->descripcion = $request->descripcion;
            $producto->precio = $request->precio;
            $producto->stock = $request->stock;
            $producto->categoria_id = $request->categoria_id;

            if ($request->hasFile('foto')) {
                // Guardamos directamente en public/uploads para que no falle nunca
                $file = $request->file('foto');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('uploads/productos'), $filename);
                $producto->imagen = 'productos/' . $filename;
            }

            $producto->save();

            if ($request->has('extras_ids')) {
                $ids = json_decode($request->extras_ids);
                $producto->complementos()->sync($ids);
            }

            return response()->json(['exito' => true, 'mensaje' => 'Producto guardado con éxito']);
        } catch (\Exception $e) {
            return response()->json(['exito' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    public function index(Request $request) {
        $query = DB::table('producto')
            ->join('categoria', 'producto.categoria_id', '=', 'categoria.id')
            ->select('producto.*', 'categoria.nombre as categoria_nombre');

        if ($request->has('solo_disponibles')) {
            $query->where('stock', '>', 0);
        }

        $productos = $query->get();

        foreach ($productos as $p) {
            if ($p->imagen) {
                // Quitamos prefijos viejos y apuntamos a la nueva carpeta uploads
                $rutaLimpia = str_replace(['storage/', 'public/'], '', $p->imagen);
                $p->imagen = url('uploads/' . $rutaLimpia);
            }
        }

        return response()->json($productos);
    }

// 2. Eliminar con seguridad
public function destroy($id) {
    try {
        // Verificamos si tiene ventas para no romper la base de datos
        $tieneVentas = DB::table('detalle_pedido')->where('producto_id', $id)->exists();
        
        if ($tieneVentas) {
            // Si tiene ventas, no lo borramos, solo le ponemos stock 0 para que no se vea
            DB::table('producto')->where('id', $id)->update(['stock' => 0]);
            return response()->json(['exito' => true, 'mensaje' => 'Producto ocultado (tiene historial de ventas)']);
        }

        $producto = DB::table('producto')->where('id', $id)->first();
        if ($producto && $producto->imagen) {
            Storage::disk('public')->delete($producto->imagen);
        }

        DB::table('producto')->where('id', $id)->delete();
        return response()->json(['exito' => true, 'mensaje' => 'Producto eliminado correctamente']);
    } catch (\Exception $e) {
        return response()->json(['exito' => false, 'mensaje' => 'No se puede eliminar: ' . $e->getMessage()], 500);
    }
}

    // NUEVO: Actualizar Producto
    public function update(Request $request, $id)
    {
        try {
            DB::table('producto')->where('id', $id)->update([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'precio' => $request->precio,
                'stock' => $request->stock,
                'categoria_id' => $request->categoria_id
            ]);

            return response()->json(['exito' => true, 'mensaje' => 'Producto actualizado correctamente']);
        } catch (\Exception $e) {
            return response()->json(['exito' => false, 'mensaje' => $e->getMessage()], 500);
        }
    }

    public function asignarExtras(Request $request) 
    {
        $producto = \App\Models\Producto::find($request->producto_id);
        if (!$producto) return response()->json(['exito' => false, 'mensaje' => 'Producto no encontrado'], 404);
        
        $ids = json_decode($request->extras_ids, true); 
        if (!is_array($ids)) $ids = [];
        
        $producto->complementos()->sync($ids);
        
        return response()->json([
            'exito' => true, 
            'mensaje' => 'Complementos actualizados para este producto'
        ]);
    }

}