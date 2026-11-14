<?php

return [
    'copias' => array_filter(array_map('trim', explode(',', env('HALLAZGOS_CORREO_CC', 'industrial@comofrasrl.com.ar,gustavog@live.com.ar')))),
];
