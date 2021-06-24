<?php


	declare( strict_types = 1 );


	namespace WPEmerge\View;

    use Tests\unit\View\MethodField;
    use WPEmerge\Contracts\ServiceProvider;
	use WPEmerge\Contracts\ViewEngineInterface;
	use WPEmerge\Contracts\ViewFactoryInterface;
    use WPEmerge\Facade\WP;
    use WPEmerge\Factories\ViewComposerFactory;


	class ViewServiceProvider extends ServiceProvider {

		public function register() : void {


		    $this->extendViews($this->config->get('root_dir') . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views');
		    $this->extendRoutes($this->config->get('root_dir') . DIRECTORY_SEPARATOR . 'routes');

		    $this->bindMethodField();

		    $this->bindGlobalContext();

            $this->bindViewServiceImplementation();

            $this->bindViewServiceInterface();

			$this->bindPhpViewEngine();

			$this->bindViewEngineInterface();

			$this->bindViewComposerCollection();


		}

		public function bootstrap() : void {

		    $context = $this->container->make(GlobalContext::class);
		    $context->add('__view', function () {
		        return $this->container->make(ViewFactory::class);
            });

		}

        private function bindGlobalContext()
        {
            $this->container->instance(GlobalContext::class, new GlobalContext());

        }

        private function bindViewServiceInterface() : void
        {

            $this->container->singleton(ViewFactoryInterface::class, function () {

                return $this->container->make(ViewFactory::class);

            });
        }

        private function bindViewServiceImplementation() : void
        {

            $this->container->singleton(ViewFactory::class, function () {

                return new ViewFactory(
                    $this->container->make(ViewEngineInterface::class),
                    $this->container->make(ViewComposerCollection::class),
                    $this->container->make(GlobalContext::class)

                );

            });
        }

        private function bindPhpViewEngine() : void
        {

            $this->container->singleton(PhpViewEngine::class, function () {

                return new PhpViewEngine(
                    new PhpViewFinder($this->config->get('views', []))
                );

            });
        }

        private function bindViewEngineInterface() : void
        {

            $this->container->singleton(ViewEngineInterface::class, function () {

                return $this->container->make(PhpViewEngine::class);

            });
        }

        private function bindViewComposerCollection() : void
        {

            $this->container->singleton(ViewComposerCollection::class, function () {

                return new ViewComposerCollection(
                    $this->container->make(ViewComposerFactory::class),
                );

            });
        }

        private function bindMethodField()
        {
            $this->container->singleton(MethodField::class, function () {
                return new MethodField($this->appKey());
            });
        }

    }
