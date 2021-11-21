<?php

declare(strict_types=1);

namespace Snicco\Session\Events;

use Snicco\Core\Events\EventObjects\CoreEvent;
use Snicco\EventDispatcher\Contracts\MappedAction;

class NewLogout extends CoreEvent implements MappedAction
{

}