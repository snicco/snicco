<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Confirmation;

    use Illuminate\Support\InteractsWithTime;
    use WPEmerge\Auth\Contracts\AuthConfirmation;
    use WPEmerge\Auth\Mail\ConfirmAuthMail;
    use WPEmerge\Contracts\MagicLink;
    use WPEmerge\Routing\UrlGenerator;
    use WPEmerge\Support\WP;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\Psr7\Response;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Mail\MailBuilder;
    use WPEmerge\Session\Session;
    use WPEmerge\View\ViewFactory;

    class EmailAuthConfirmation implements AuthConfirmation
    {

        /**
         * @var ViewFactory
         */
        private $view_factory;

        /**
         * @var MagicLink
         */
        private $magic_link;

        /**
         * @var UrlGenerator
         */
        private $url;

        public function __construct(MagicLink $magic_link, ViewFactory $view_factory, UrlGenerator $url)
        {

            $this->magic_link = $magic_link;
            $this->view_factory = $view_factory;
            $this->url = $url;
        }

        public function confirm(Request $request)
        {

            $valid = $this->magic_link->hasValidSignature($request, true);

            if ( ! $valid ) {

                return ['message' => 'Confirmation link invalid or expired.'];

            }

            $this->magic_link->invalidate($request->fullUrl());

            return true;

        }

        public function viewResponse(Request $request)
        {

            return $this->view_factory->make('auth-layout')
                                      ->with([
                                          'view' => 'auth-confirm-via-email',
                                          'post_to' => $this->url->toRoute('auth.confirm.email')
                                      ]);

        }


    }