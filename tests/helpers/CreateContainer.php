<?php


    declare(strict_types = 1);


    namespace Tests\helpers;

    use Contracts\ContainerAdapter;
    use SniccoAdapter\BaseContainerAdapter;

    /**
     * @internal
     */
    trait CreateContainer
    {

        public function createContainer() :ContainerAdapter {

            return new BaseContainerAdapter();

        }

    }