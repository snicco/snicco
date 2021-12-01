<?php

declare(strict_types=1);

$router->get('underscore-route', function () {
    throw new Exception('Route file with an underscore was loaded automatically.');
});


