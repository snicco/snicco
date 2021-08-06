<?php


    declare(strict_types = 1);


    namespace Snicco\Http;

    use Snicco\Contracts\ViewFactoryInterface;
    use Snicco\Routing\UrlGenerator;

    class Controller
    {


        /**
         * @var ControllerMiddleware[]
         */
        private array                  $middleware = [];
        protected ViewFactoryInterface $view_factory;
        protected ResponseFactory      $response_factory;
        protected UrlGenerator         $url;

        public function getMiddleware(string $method = null) : array
        {

            return collect($this->middleware)
                ->filter(fn(ControllerMiddleware $middleware) => $middleware->appliesTo($method))
                ->map(fn(ControllerMiddleware $middleware) => $middleware->name())
                ->values()
                ->all();


        }

        protected function middleware(string $middleware_name) : ControllerMiddleware
        {

            return $this->middleware[] = new ControllerMiddleware($middleware_name);


        }

        public function giveViewFactory(ViewFactoryInterface $view_factory)
        {
            $this->view_factory = $view_factory;
        }

        public function giveResponseFactory(ResponseFactory $response_factory)
        {
            $this->response_factory = $response_factory;
        }

        public function giveUrlGenerator(UrlGenerator $url)
        {
            $this->url = $url;
        }


    }