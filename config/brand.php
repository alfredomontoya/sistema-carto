<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Identidad de marca (valores por defecto)
    |--------------------------------------------------------------------------
    | Estos valores pueden sobrescribirse en tiempo de ejecución desde la
    | pantalla de Ajustes (tabla settings). Las claves usadas en BD son:
    |   brand.app_name
    |   brand.primary_color
    |   brand.secondary_color
    |   brand.logo          (nombre de archivo dentro de storage/app/public/brand)
    |   brand.favicon       (nombre de archivo dentro de storage/app/public/brand)
    |
    */

    'app_name' => env('APP_NAME', 'Sistema de Información'),

    'primary_color' => '#1d4ed8',

    'secondary_color' => '#0d9488',

    'logo' => null,

    'favicon' => null,

];
