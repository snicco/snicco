<?php

declare(strict_types=1);


namespace Snicco\Component\Session\Exception;

use LogicException;

final class SessionWasAlreadyInvalidated extends LogicException
{

}