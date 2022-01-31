<?php

declare(strict_types=1);

namespace Snicco\Component\Eloquent\Tests\fixtures\Model;

use Snicco\Component\Eloquent\Illuminate\WPModel;
use Snicco\Component\Eloquent\Illuminate\WithFactory;

class TestWPModel extends WPModel
{
    
    use WithFactory;
}