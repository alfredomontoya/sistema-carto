<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Puestos por defecto de áreas nuevas
    |--------------------------------------------------------------------------
    | Lista editable de puestos que el diálogo de áreas ofrece marcados al
    | crear una nueva área. Cada entrada necesita:
    |   name       (mayúsculas, ej. JEFE)
    |   code       (minúsculas, único por área, ej. jefe)
    |   sort_order (orden de visualización)
    |
    */

    'defaults' => [
        ['name' => 'JEFE', 'code' => 'jefe', 'sort_order' => 0],
        ['name' => 'PROFESIONAL', 'code' => 'profesional', 'sort_order' => 1],
        ['name' => 'ABOGADO', 'code' => 'abogado', 'sort_order' => 2],
        ['name' => 'TECNICO', 'code' => 'tecnico', 'sort_order' => 3],
        ['name' => 'ASISTENTE', 'code' => 'asistente', 'sort_order' => 4],
        ['name' => 'SECRETARIA', 'code' => 'secretaria', 'sort_order' => 5],
        ['name' => 'MENSAJERO', 'code' => 'mensajero', 'sort_order' => 6],
    ],

];
