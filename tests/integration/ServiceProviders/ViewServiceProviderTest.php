<?php


    declare(strict_types = 1);


    namespace Tests\integration\ServiceProviders;

    use Tests\integration\IntegrationTest;
    use Tests\stubs\TestApp;
    use Tests\unit\View\MethodField;
    use WPEmerge\Contracts\ViewEngineInterface;
    use WPEmerge\Contracts\ViewFactoryInterface;
    use WPEmerge\View\GlobalContext;
    use WPEmerge\View\PhpViewEngine;
    use WPEmerge\View\ViewFactory;
    use WPEmerge\View\ViewComposerCollection;

    class ViewServiceProviderTest extends IntegrationTest
    {

        /** @test */
        public function the_global_context_is_a_singleton()
        {

            $this->newTestApp();

            /** @var GlobalContext $context */
            $context = TestApp::resolve(GlobalContext::class);

            $this->assertInstanceOf(GlobalContext::class, $context);

            $this->assertSame([], $context->get());

            TestApp::globals('foo', 'bar');

            $this->assertSame(['foo' => 'bar'], $context->get());

        }

        /** @test */
        public function the_view_service_is_resolved_correctly()
        {

            $this->newTestApp();

            $this->assertInstanceOf(ViewFactory::class, TestApp::resolve(ViewFactoryInterface::class));

        }

        /** @test */
        public function the_view_engine_is_resolved_correctly()
        {

            $this->newTestApp();

            $this->assertInstanceOf(PhpViewEngine::class, TestApp::resolve(ViewEngineInterface::class));

        }

        /** @test */
        public function the_view_composer_collection_is_resolved_correctly()
        {

            $this->newTestApp();

            $this->assertInstanceOf(ViewComposerCollection::class, TestApp::resolve(ViewComposerCollection::class));

        }



        /** @test */
        public function the_method_field_can_be_resolved () {

            $this->newTestApp();

            $this->assertInstanceOf(MethodField::class, TestApp::resolve(MethodField::class));

        }


    }
