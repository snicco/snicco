<?php

declare(strict_types=1);

namespace Snicco\Bundle\Templating\Option;

final class TemplatingOption
{
    /**
     * @var string
     */
    public const DIRECTORIES = 'directories';

    /**
     * @var string
     */
    public const VIEW_FACTORIES = 'factories';

    /**
     * @var string
     */
    public const VIEW_COMPOSERS = 'composers';

    /**
     * @var string
     */
    public const PARENT_VIEW_PARSE_LENGTH = 'parent_view_parse_length';
}
