<?php


    declare(strict_types = 1);


    namespace Tests\integration\HttpKernel;

    use Contracts\ContainerAdapter;
    use SniccoAdapter\BaseContainerAdapter;

    trait CreateContainer
    {

        public function createContaiener() :ContainerAdapter {

            return new BaseContainerAdapter();

        }

    }