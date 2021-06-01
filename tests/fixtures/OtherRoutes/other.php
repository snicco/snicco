<?php


    declare(strict_types = 1);

    use Tests\stubs\TestApp;

    $router = TestApp::route();


    $router->get('other', function () {

        return 'other';

    });