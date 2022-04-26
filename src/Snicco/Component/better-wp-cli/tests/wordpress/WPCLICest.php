<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCLI\Tests\wordpress;

use Snicco\Component\BetterWPCLI\Tests\CliTester;
use Snicco\Component\BetterWPCLI\Verbosity;

final class WPCLICest
{
    public function test_basic_command(CliTester $I): void
    {
        $I->cli(['snicco', 'argument', 'calvin']);

        $I->seeInShellOutput('Hello calvin');
        $I->seeResultCodeIs(0);
    }

    public function test_repeating_arguments(CliTester $I): void
    {
        $I->cli(['snicco', 'repeating', 'calvin', 'marlon']);

        $I->seeInShellOutput('Hello calvin,marlon');
        $I->seeResultCodeIs(0);
    }

    public function test_options(CliTester $I): void
    {
        $I->cli(['snicco', 'option', '--option=foo']);

        $I->seeInShellOutput('foo');
        $I->seeResultCodeIs(0);
    }

    public function test_flags(CliTester $I): void
    {
        $I->cli(['snicco', 'flag']);
        $I->seeInShellOutput('NULL');
        $I->seeResultCodeIs(0);

        $I->cli(['snicco', 'flag', '--flag']);
        $I->seeInShellOutput('TRUE');
        $I->seeResultCodeIs(0);

        $I->cli(['snicco', 'flag', '--no-flag']);
        $I->seeInShellOutput('FALSE');
        $I->seeResultCodeIs(0);
    }

    public function test_interactivity(CliTester $I): void
    {
        $I->cli(['snicco', 'interactive']);
        $I->seeInShellOutput('INTERACTIVE');
        $I->seeResultCodeIs(0);

        $I->cli(['snicco', 'interactive', '--no-interaction']);
        $I->seeInShellOutput('NOT INTERACTIVE');
        $I->seeResultCodeIs(0);
    }

    public function test_colors(CliTester $I): void
    {
        $I->cli(['snicco', 'colors']);
        $I->seeInShellOutput('DECORATED');
        $I->seeResultCodeIs(0);

        $I->cli(['snicco', 'colors', '--color']);
        $I->seeInShellOutput('DECORATED');
        $I->seeResultCodeIs(0);

        $I->cli(['snicco', 'colors', '--ansi']);
        $I->seeInShellOutput('DECORATED');
        $I->seeResultCodeIs(0);

        $I->cli(['snicco', 'colors', '--no-color']);
        $I->seeInShellOutput('NOT DECORATED');
        $I->seeResultCodeIs(0);

        $I->cli(['snicco', 'colors', '--no-ansi']);
        $I->seeInShellOutput('NOT DECORATED');
        $I->seeResultCodeIs(0);

        $I->cli(['snicco', 'colors', '--color --no-ansi']);
        $I->seeInShellOutput('DECORATED');
        $I->seeResultCodeIs(0);

        $I->cli(['snicco', 'colors', '--no-color --ansi']);
        $I->seeInShellOutput('NOT DECORATED');
        $I->seeResultCodeIs(0);
    }

    public function test_verbosity(CliTester $I): void
    {
        $I->cli(['snicco', 'verbosity']);
        $I->seeInShellOutput((string) Verbosity::NORMAL);
        $I->seeResultCodeIs(0);

        $I->cli(['snicco', 'verbosity', '--v']);
        $I->seeInShellOutput((string) Verbosity::VERBOSE);
        $I->seeResultCodeIs(0);

        $I->cli(['snicco', 'verbosity', '--vv']);
        $I->seeInShellOutput((string) Verbosity::VERY_VERBOSE);
        $I->seeResultCodeIs(0);

        $I->cli(['snicco', 'verbosity', '--vvv']);
        $I->seeInShellOutput((string) Verbosity::DEBUG);
        $I->seeResultCodeIs(0);

        $I->cli(['snicco', 'verbosity', '--v', '--debug']);
        $I->seeInShellOutput((string) Verbosity::DEBUG);
        $I->seeResultCodeIs(0);

        $I->cli(['snicco', 'verbosity', '--vvv', '--quiet']);
        $I->seeInShellOutput((string) Verbosity::DEBUG);
        $I->seeResultCodeIs(0);
    }
}
