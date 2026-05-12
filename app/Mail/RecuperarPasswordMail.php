<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RecuperarPasswordMail extends Mailable
{
    
    use Queueable, SerializesModels;

   public $url;
public function __construct($url) { $this->url = $url; }
public function build() {
    return $this->subject('Restablecer Contraseña - Bukanas')->view('emails.reset');
}
    
}