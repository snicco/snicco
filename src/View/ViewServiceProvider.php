<?php


	declare( strict_types = 1 );


	namespace WPEmerge\View;

	use Illuminate\Config\Repository;
    use WPEmerge\Contracts\ServiceProvider;
	use WPEmerge\Contracts\ViewEngineInterface;
	use WPEmerge\Contracts\ViewFinderInterface;
	use WPEmerge\Contracts\ViewFactoryInterface;
	use WPEmerge\View\PhpViewEngine;
	use WPEmerge\View\PhpViewFinder;
	use WPEmerge\Support\VariableBag;
	use WPEmerge\View\ViewFactory;
	use WPEmerge\View\ViewComposerCollection;
	use WPEmerge\Factories\ViewComposerFactory;


	class ViewServiceProvider extends ServiceProvider {

		public function register() : void {


		    $this->bindGlobalContext();

            $this->bindViewServiceImplementation();

            $this->bindViewServiceInterface();

			$this->bindPhpViewEngine();

			$this->bindViewEngineInterface();

			$this->bindViewComposerCollection();


		}

		public function bootstrap() : void {
			// Nothing to bootstrap.
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

    }
