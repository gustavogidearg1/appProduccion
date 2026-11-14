<?php

namespace App\Mail;

use App\Models\HallazgoCalidad;
use Illuminate\Mail\Mailable;

class HallazgoAsignado extends Mailable
{
    public function __construct(public HallazgoCalidad $hallazgo) {}

    public function build()
    {
        return $this->subject("Hallazgo {$this->hallazgo->tipo}-{$this->hallazgo->numero} asignado · App Producción")
            ->view('emails.hallazgos.asignado');
    }
}
