<?php

declare(strict_types=1);

$router->get('underscore-api', function () {
    throw new Exception('API route file was loaded although it starts with an "_"');
});