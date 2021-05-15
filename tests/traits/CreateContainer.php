<?php


    declare(strict_types = 1);


    namespace Tests\traits;

    use Contracts\ContainerAdapter;
    use SniccoAdapter\BaseContainerAdapter;

    trait CreateContainer
    {

        public function createContainer() :ContainerAdapter {

            return new BaseContainerAdapter();

        }

    }