<?php

declare(strict_types=1);

/** @var ArrayAccess $global1 */

if (isset($global1['foo.bar'])) {
    echo 'Isset works';
} else {
    echo 'Isset does not work';
}
