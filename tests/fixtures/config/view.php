<?php


    declare(strict_types = 1);

return [

   'paths' => [

       VIEWS_DIR,
       VIEWS_DIR.DS.'subdirectory',

   ],

    'composers' => [

        'Tests\fixtures\ViewComposers',

    ]

];