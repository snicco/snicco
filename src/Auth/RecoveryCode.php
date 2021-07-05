<?php


    declare(strict_types = 1);


    namespace WPMvc\Auth;

    use WPMvc\Support\Str;

    class RecoveryCode
    {
        public static function generate() : string
        {
            return Str::random(10).'-'.Str::random(10);
        }
    }