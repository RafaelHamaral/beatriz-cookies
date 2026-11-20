<?php

return [

    /*
    | As fotos ficam em storage/app/catalog, fora do document root.
    | JPEG e PNG são regravados. WebP é aceito depois da leitura do tipo
    | real porque esta instalação do PHP não regrava WebP.
    */

    'max_bytes' => 5 * 1024 * 1024,

    'min_edge' => 400,

    'max_edge' => 4000,

    'max_images' => 8,

];
