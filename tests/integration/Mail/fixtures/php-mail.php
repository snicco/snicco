<?php

declare(strict_types=1);

?>
<h1>Hi <?= $recipient->getName() ?></h1>
<p><?= $foo ?></p>
<p><?= isset($bar) ? 'BAR_AVAILABLE' : 'BAR_NOT_AVAILABLE_CAUSE_PRIVATE_PROPERTY' ?></p>