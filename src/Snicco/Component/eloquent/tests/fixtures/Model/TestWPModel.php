<?php

declare(strict_types=1);

namespace Snicco\Component\Eloquent\Tests\fixtures\Model;

use Snicco\Component\Eloquent\Illuminate\WithFactory;
use Snicco\Component\Eloquent\Illuminate\WPModel;

class TestWPModel extends WPModel
{

    use WithFactory;
}