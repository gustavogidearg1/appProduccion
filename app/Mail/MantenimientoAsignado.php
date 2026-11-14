<?php
namespace App\Mail;
use Illuminate\Mail\Mailable;
class MantenimientoAsignado extends Mailable {
 public function __construct(public object $orden, public string $nombre) {}
 public function build() { return $this->subject('Hallazgo de mantenimiento OT-'.$this->orden->id.' asignado · App Producción')->view('emails.mantenimiento.asignado'); }
}
