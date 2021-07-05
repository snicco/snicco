<?php


    declare(strict_types = 1);


    namespace Tests\integration\Blade\Components;

    use Tests\fixtures\TestDependencies\Foo;
    use WPMvc\Blade\BladeComponent;

    class Dependency extends BladeComponent
    {

        /**
         * @var Foo
         */
        public $foo;

        public $message;

        protected $except = ['foo'];

        public function __construct(Foo $foo, $message)
        {
            $this->foo = $foo;
            $this->message = $foo->foo . $message;
        }

        public function render()
        {

            return $this->view('components.with-dependency');

        }

    }