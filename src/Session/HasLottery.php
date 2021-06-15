<?php


    declare(strict_types = 1);


    namespace WPEmerge\Session;

    trait HasLottery
    {

        protected function hitsLottery (array $lottery_config ) {

            return random_int(1, $lottery_config[1]) <= $lottery_config[0];

        }

    }