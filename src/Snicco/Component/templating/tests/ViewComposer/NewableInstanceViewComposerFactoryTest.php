<?php

declare(strict_types=1);


namespace Snicco\Component\Templating\Tests\ViewComposer;

use PHPUnit\Framework\TestCase;
use Snicco\Component\Templating\Exception\BadViewComposer;
use Snicco\Component\Templating\View\View;
use Snicco\Component\Templating\ViewComposer\NewableInstanceViewComposerFactory;
use Snicco\Component\Templating\ViewComposer\ViewComposer;
use stdClass;

use function sprintf;

final class NewableInstanceViewComposerFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function test_friendly_exception_if_a_composer_cant_be_instantiated(): void
    {
        $factory = new NewableInstanceViewComposerFactory();

        $this->expectException(BadViewComposer::class);

        $factory->create(ComplexComposer::class);
        $this->expectExceptionMessage(
            sprintf('The view composer class [%s] is not a newable.', ComplexComposer::class)
        );
    }

    /**
     * @test
     * @psalm-suppress ArgumentTypeCoercion
     * @psalm-suppress UndefinedClass
     */
    public function test_exception_for_bad_class(): void
    {
        $factory = new NewableInstanceViewComposerFactory();

        $this->expectException(BadViewComposer::class);
        $this->expectExceptionMessage(
            'The view composer class [ComplexComposer] is not a newable.'
        );

        $factory->create('ComplexComposer');
    }
}

class ComplexComposer implements ViewComposer
{
    private stdClass $stdClass;

    public function __construct(stdClass $stdClass)
    {
        $this->stdClass = $stdClass;
    }

    public function compose(View $view): View
    {
        return $view;
    }
}
