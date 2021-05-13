<?php


    declare(strict_types = 1);


    namespace Tests;


    class PsrPlayGround extends \PHPUnit\Framework\TestCase
    {

        /** @test */
        public function basic_test () {

            $a = array(1, 2, 3, 4, 5);
            $x = array();

            $result = array_reduce($a, [$this, "sum"], 10 );


        }

        function sum($carry, $item)
        {
            $carry += $item;
            return $carry;
        }

    }



    function product($carry, $item)
    {
        $carry *= $item;
        return $carry;
    }



