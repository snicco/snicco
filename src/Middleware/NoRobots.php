<?php


    declare(strict_types = 1);


    namespace Snicco\Middleware;

    use Psr\Http\Message\ResponseInterface;
    use Snicco\Contracts\Middleware;
    use Snicco\Http\Delegate;
    use Snicco\Http\Psr7\Request;
    use Snicco\Http\Psr7\Response;

    class NoRobots extends Middleware
    {

        /**
         * @var bool
         */
        private $archive;

        /**
         * @var bool
         */
        private $follow;

        /**
         * @var bool
         */
        private $index;

        public function __construct($noindex = 'noindex', $nofollow = 'nofollow', $noarchive = 'noarchive')
        {

            $this->index = strtolower($noindex) !== 'noindex';
            $this->follow = strtolower($nofollow) !== 'nofollow';
            $this->archive = strtolower($noarchive) !== 'noarchive';

        }

        public function handle(Request $request, Delegate $next):ResponseInterface
        {

            /** @var Response $response */
            $response = $next($request);

            if ( ! $this->archive ) {

                $response = $response->noArchive();

            }

            if ( ! $this->index ) {

                $response =  $response->noIndex();

            }

            if ( ! $this->follow ) {

                $response = $response->noFollow();

            }

            return $response;


        }

    }