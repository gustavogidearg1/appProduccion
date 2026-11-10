<?php

// Subir temporalmente a public. Configurar una clave aleatoria de 32 caracteres o más.
$clave = '';

header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');

if (strlen($clave) < 32) {
    http_response_code(404);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: text/html; charset=UTF-8');
    echo '<form method="post"><label>Clave temporal <input name="clave" type="password" required></label> <button>Crear enlace</button></form>';
    exit;
}

header('Content-Type: text/plain; charset=UTF-8');
if (!is_string($_POST['clave'] ?? null) || !hash_equals($clave, $_POST['clave'])) {
    http_response_code(403);
    exit('Clave incorrecta.');
}

$origen = realpath(__DIR__ . '/../storage/app/public');
$enlace = __DIR__ . '/storage';

if ($origen === false || !is_dir($origen)) {
    http_response_code(409);
    exit('Creá storage/app/public junto al proyecto antes de continuar.');
}
if (is_link($enlace) && realpath($enlace) === $origen) {
    exit('El enlace ya existe. Eliminá este archivo PHP desde cPanel.');
}
if (file_exists($enlace) || is_link($enlace)) {
    http_response_code(409);
    exit('Ya existe public/storage. Revisalo en cPanel antes de continuar.');
}
if (!function_exists('symlink') || !@symlink($origen, $enlace)) {
    http_response_code(500);
    exit('El hosting no pudo crear el enlace. Consultá a Wiroos.');
}

echo 'Enlace creado. Eliminá este archivo PHP desde cPanel.';
