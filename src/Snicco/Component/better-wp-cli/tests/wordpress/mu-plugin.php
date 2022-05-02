<?php

declare(strict_types=1);

use Snicco\Component\BetterWPCLI\CommandLoader\ArrayCommandLoader;
use Snicco\Component\BetterWPCLI\Tests\wordpress\Commands\ArgumentCommand;
use Snicco\Component\BetterWPCLI\Tests\wordpress\Commands\ColorsCommand;
use Snicco\Component\BetterWPCLI\Tests\wordpress\Commands\FlagCommand;
use Snicco\Component\BetterWPCLI\Tests\wordpress\Commands\InteractiveCommand;
use Snicco\Component\BetterWPCLI\Tests\wordpress\Commands\OptionCommand;
use Snicco\Component\BetterWPCLI\Tests\wordpress\Commands\RepeatingArgumentCommand;
use Snicco\Component\BetterWPCLI\Tests\wordpress\Commands\VerbosityCommand;
use Snicco\Component\BetterWPCLI\WPCLIApplication;

$wp_browser = getenv('WPBROWSER_HOST_REQUEST');
$cli = defined(WP_CLI::class);
if (! $cli) {
    return;
}

if (! $wp_browser) {
    return;
}

$application = new WPCLIApplication('snicco', new ArrayCommandLoader([
    ArgumentCommand::class,
    RepeatingArgumentCommand::class,
    OptionCommand::class,
    FlagCommand::class,
    InteractiveCommand::class,
    ColorsCommand::class,
    VerbosityCommand::class,
]));

$application->registerCommands();
