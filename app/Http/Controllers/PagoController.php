<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class PagoController extends Controller
{
    public function crearPaymentIntent(Request $request)
    {
        $stripeSecret = env('STRIPE_SECRET', 'tu_clave_secreta_aqui');
        Stripe::setApiKey($stripeSecret);

        try {
            $montoOriginal = (float) $request->total; 
            $montoStripe = (int) ($montoOriginal * 100);

            $paymentIntent = PaymentIntent::create([
                'amount' => $montoStripe,
                'currency' => 'usd', // CAMBIO TEMPORAL: 'usd' para descartar bloqueos de región
                'payment_method_types' => ['card'],
            ]);

            return response()->json([
                'exito' => true,
                'clientSecret' => $paymentIntent->client_secret,
                'paymentIntentId' => $paymentIntent->id
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('STRIPE FATAL ERROR: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'exito' => false, 
                'mensaje' => 'Error al comunicar con Stripe: ' . $e->getMessage()
            ], 500);
        }
    }
}
