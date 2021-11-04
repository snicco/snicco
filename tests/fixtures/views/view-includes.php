<?php

declare(strict_types=1);

use Tests\stubs\TestApp;

echo 'Hello ';
TestApp::render('subdirectory.included-subview.php');