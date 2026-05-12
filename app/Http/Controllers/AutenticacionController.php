<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\RecuperarPasswordMail;
use Carbon\Carbon;
use App\Mail\OtpPasswordMail;

class AutenticacionController extends Controller
{
    public function login(Request $request) {
        $acceso = DB::table('dts_acceso')->where('correo', trim($request->correo))->first();

        if ($acceso && Hash::check($request->contrasena, $acceso->contrasena_hash)) {
            // --- Validación de Estado Global (Vacaciones/Cierre de App) ---
            $config = \Illuminate\Support\Facades\Storage::exists('app_config.json') 
                ? json_decode(\Illuminate\Support\Facades\Storage::get('app_config.json'), true) 
                : ['app_activa' => true, 'mensaje_bloqueo' => '', 'dias_vacaciones' => []];

            $rolEsAdmin = strtolower($acceso->rol) === 'admin';
            $hoy = date('Y-m-d');
            $vacaciones = $config['dias_vacaciones'] ?? [];
            $esDiaVacaciones = in_array($hoy, $vacaciones);
            $appActiva = $config['app_activa'] ?? true;

            // Si NO es admin y la app está cerrada o es vacaciones
            if (!$rolEsAdmin) {
                if (!$appActiva) {
                    return response()->json([
                        'success' => false, 
                        'mensaje' => $config['mensaje_bloqueo'] ?: 'La aplicación se encuentra temporalmente fuera de servicio.'
                    ], 403);
                }

                if ($esDiaVacaciones) {
                    return response()->json([
                        'success' => false,
                        'mensaje' => 'Hoy nos encontramos cerrados por vacaciones.'
                    ], 403);
                }
            }
            // -------------------------------------------------------------

            DB::table('dts_acceso')
                ->where('id', $acceso->id)
                ->update(['ultimo_acceso' => now()]);

            $persona = DB::table('persona')->where('dni', $acceso->dni)->first();

            return response()->json([
                'success' => true,
                'mensaje' => 'Bienvenido',
                'rol'     => strtolower($acceso->rol),
                'dni'     => $acceso->dni,
                'nombre'  => $persona ? $persona->P_nombre : 'Usuario',
                // Enviamos el estado para que la app lo sepa (aunque sea admin)
                'app_activa' => $appActiva && !$esDiaVacaciones,
                'mensaje_bloqueo' => $esDiaVacaciones ? 'Cerrado por vacaciones.' : ($config['mensaje_bloqueo'] ?: '')
            ]);
        }

        return response()->json(['success' => false, 'mensaje' => 'Credenciales incorrectas'], 401);
    }

    public function registro(Request $request)
    {
        try {
            DB::beginTransaction();

            $dni = $request->dni;
            $correo = trim($request->correo);

            // Validaciones de existencia
            if (DB::table('persona')->where('dni', $dni)->exists()) {
                return response()->json(['exito' => false, 'mensaje' => 'El DNI ya está registrado'], 400);
            }
            if (DB::table('dts_acceso')->where('correo', $correo)->exists()) {
                return response()->json(['exito' => false, 'mensaje' => 'El correo ya está en uso'], 400);
            }

            // 1. Insertar Persona
            DB::table('persona')->insert([
                'dni'        => $dni,
                'P_nombre'   => $request->p_nombre,
                'S_nombre'   => $request->s_nombre ?? '',
                'P_apellido' => $request->p_apellido,
                'S_apellido' => $request->s_apellido ?? '',
                'edad'       => $request->edad,
                'genero'     => $request->genero,
            ]);

            // 2. Insertar Contacto
            DB::table('dts_contacto')->insert([
                'correo'   => $correo,
                'telefono' => $request->telefono ?? '',
                'dni'      => $dni
            ]);

            // 3. Insertar Ubicación
            DB::table('dts_ubicacion')->insert([
                'pais'      => $request->pais ?? '',
                'ciudad'    => $request->ciudad ?? '',
                'direccion' => $request->direccion ?? '',
                'dni'       => $dni
            ]);

            // 4. Insertar Acceso
            DB::table('dts_acceso')->insert([
                'dni'             => $dni,
                'correo'          => $correo,
                'contrasena_hash' => Hash::make($request->contrasena),
                'rol'             => 'cliente',
                'estado'          => 'activo',
                'ultimo_acceso'   => now()
            ]);

            DB::commit();
            return response()->json(['exito' => true, 'mensaje' => 'Registro exitoso']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['exito' => false, 'mensaje' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function enviarToken(Request $request) 
    {
        $correo = trim($request->correo);
        $user = DB::table('dts_acceso')->where('correo', $correo)->first();

        if (!$user) {
            return response()->json(['exito' => false, 'mensaje' => 'Correo no registrado en el sistema'], 404);
        }

        $token = Str::random(64);
        
        // Guardar Token
        DB::table('recuperacion_password')->insert([
            'dni'              => $user->dni,
            'token'            => $token,
            'fecha_expiracion' => Carbon::now()->addMinutes(15),
            'usado'            => false
        ]);

        $url = url("/password/reset/" . $token);
        
        try {
            Mail::to($correo)->send(new RecuperarPasswordMail($url));
            return response()->json(['exito' => true, 'mensaje' => 'Enlace enviado al correo']);
        } catch (\Exception $e) {
            return response()->json(['exito' => false, 'mensaje' => 'Error al enviar email: ' . $e->getMessage()], 500);
        }
    }

    
    public function obtenerPerfil($dni) {
        $perfil = DB::table('persona')
            ->join('dts_contacto', 'persona.dni', '=', 'dts_contacto.dni')
            ->join('dts_ubicacion', 'persona.dni', '=', 'dts_ubicacion.dni')
            ->where('persona.dni', $dni)
            ->first();
        return response()->json($perfil);
    }




    public function enviarOtp(Request $request) {
        // 1. Validar que el correo exista en tu tabla dts_acceso
        $request->validate(['email' => 'required|email']);
        
        $usuario = DB::table('dts_acceso')->where('correo', $request->email)->first();

        if (!$usuario) {
            return response()->json(['success' => false, 'message' => 'Correo no registrado'], 404);
        }

        // 2. Generar código de 6 dígitos
        $otp = rand(100000, 999999);

        // 3. Guardar o actualizar en password_otps
        DB::table('password_otps')->updateOrInsert(
            ['email' => $request->email],
            [
                'otp' => $otp, 
                'expires_at' => now()->addMinutes(15), 
                'created_at' => now()
            ]
        );

        // 4. Enviar el correo usando el sistema de Laravel
        try {
            Mail::to($request->email)->send(new OtpPasswordMail($otp));
            return response()->json(['success' => true, 'message' => 'Código enviado a tu correo']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al enviar correo'], 500);
        }
    }

    public function validarOtp(Request $request) {
        $found = DB::table('password_otps')
            ->where('email', $request->email)
            ->where('otp', $request->otp)
            ->where('expires_at', '>', now())
            ->first();

        if (!$found) {
            return response()->json(['success' => false, 'message' => 'Código inválido o expirado'], 422);
        }

        return response()->json(['success' => true, 'message' => 'Código válido']);
    }

    public function resetConOtp(Request $request) {
        // Validar que el código sea correcto antes de cambiar
        $valido = DB::table('password_otps')
            ->where('email', $request->email)
            ->where('otp', $request->otp)
            ->where('expires_at', '>', now())
            ->exists();

        if (!$valido) {
            return response()->json(['success' => false, 'message' => 'Sesión de recuperación inválida'], 422);
        }

        // Actualizar la contraseña en dts_acceso
        DB::table('dts_acceso')
            ->where('correo', $request->email)
            ->update(['contrasena_hash' => Hash::make($request->password)]);

        // Borrar el OTP para que no se use de nuevo
        DB::table('password_otps')->where('email', $request->email)->delete();

        return response()->json(['success' => true, 'message' => 'Contraseña actualizada correctamente']);
    }
}

