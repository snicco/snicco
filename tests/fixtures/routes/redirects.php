<?php

declare(strict_types=1);

use Tests\stubs\TestApp;

TestApp::route()->redirect('/location-a', '/location-b');
