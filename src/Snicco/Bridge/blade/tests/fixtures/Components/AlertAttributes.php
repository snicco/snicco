<?php

declare(strict_types=1);

namespace Snicco\Bridge\Blade\Tests\fixtures\Components;

use Snicco\Bridge\Blade\BladeComponent;

class AlertAttributes extends BladeComponent
{

    /**
     * The alert type.
     */
    public string $type;

    /**
     * The alert message.
     */
    public string $message;

    /**
     * Create the component instance.
     *
     * @param string $type
     * @param string $message
     *
     * @return void
     */
    public function __construct(string $type, string $message)
    {
        $this->type = $type;
        $this->message = $message;
    }

    public function render()
    {
        return $this->view('components.alert-attributes');
    }

}