<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;



class OtpPasswordMail extends Mailable
{
    use Queueable, SerializesModels;
    public $otp;
    public function __construct($otp) {
        $this->otp = $otp;
    }
    public function build() {
        return $this->subject('Código de Recuperación - Bukanas')
                    ->view('emails.otp'); // Asegúrate de que apunte a la vista correcta
    }
}
