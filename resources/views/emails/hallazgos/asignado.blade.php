<!doctype html>
<html lang="es"><head><meta charset="utf-8"><title>Hallazgo asignado</title></head>
<body style="margin:0;background:#f3f5f7;font-family:Arial,sans-serif;color:#233343">
<div style="max-width:640px;margin:24px auto;background:white;border:1px solid #dce2e7">
<div style="padding:24px;background:#244e69;color:white"><strong>APP PRODUCCIÓN · CALIDAD</strong><h1 style="font-size:24px">Tenés un hallazgo asignado</h1></div>
<div style="padding:24px">
<p>Hola, {{ $hallazgo->responsable->name }}:</p>
<p>Se registró el hallazgo <strong>{{ $hallazgo->tipo }}-{{ $hallazgo->numero }}</strong> y sos responsable de su seguimiento y respuesta.</p>
<h2 style="font-size:20px">{{ $hallazgo->titulo }}</h2>
<p><strong>Tipo:</strong> {{ \App\Models\HallazgoCalidad::TIPOS[$hallazgo->tipo] }}<br>
<strong>Origen:</strong> {{ $hallazgo->origen }}<br><strong>Registrado por:</strong> {{ $hallazgo->autor->name }}<br>
<strong>Fecha:</strong> {{ $hallazgo->created_at->format('d/m/Y') }}
@if($hallazgo->plan)<br><strong>Plan:</strong> #{{ $hallazgo->plan->numero }}@endif
@if($hallazgo->unidad)<br><strong>Chasis:</strong> {{ $hallazgo->unidad->chasis }}@endif</p>
<div style="white-space:pre-wrap;padding:16px;background:#f3f5f7">{{ $hallazgo->descripcion }}</div>
<p>Ingresá para revisar el detalle, registrar la respuesta y definir las acciones y la fecha de compromiso.</p>
<p style="margin:28px 0"><a href="{{ route('hallazgos.show', $hallazgo) }}" style="background:#244e69;color:white;padding:12px 20px;text-decoration:none">Ver y responder hallazgo</a></p>
<p style="font-size:12px;color:#596875">El acceso requiere tu usuario de App Producción. Este aviso se generó al registrar el hallazgo.</p>
</div></div></body></html>
