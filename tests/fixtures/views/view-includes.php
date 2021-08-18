<?php

declare(strict_types=1);

use Tests\stubs\TestApp;

echo 'Hello ';
TestApp::render('included-subview.php');