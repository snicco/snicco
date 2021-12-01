<?php

declare(strict_types=1);

use Tests\Codeception\shared\TestApp\TestApp;

TestApp::route()->redirect('/location-a', '/location-b');
