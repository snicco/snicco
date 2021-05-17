<?php


    declare(strict_types = 1);


    namespace Tests\integration\Blade\Components;

    use WPEmerge\Blade\BladeComponent;

    class InlineComponent extends BladeComponent
    {

        private $content;

        public function __construct($content)
        {
            $this->content = strtoupper($content);
        }

        public function render()
        {

            return <<<'blade'
Content:{{$this-content}},SLOT:{{ $slot }}
blade;
        }

    }