<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Controllers;

    use WPEmerge\Auth\Mail\ResetPasswordMail;
    use WPEmerge\Auth\Traits\SendsPasswordResetMails;
    use WPEmerge\ExceptionHandling\Exceptions\AuthorizationException;
    use WPEmerge\Http\Responses\RedirectResponse;
    use WPEmerge\Support\WP;
    use WPEmerge\Http\Controller;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Mail\MailBuilder;
    use WPEmerge\Support\Arr;

    /**
     * This Controller performs the same logic that happens inside user.php
     * when password reset emails ar sent from the admin interface.
     *
     * This Controller sends password reset emails that are compatible with the Auth Package by
     * creating a signed link to the password reset route.
     *
     */
    class BulkPasswordResetEmailController extends Controller
    {

        use SendsPasswordResetMails;

        /**
         * @var MailBuilder
         */
        private $mail;

        protected $lifetime = 300;

        protected $error_message = 'Sorry, you are not allowed to perform this action';

        public function __construct(MailBuilder $mail)
        {
            $this->mail = $mail;
        }

        public function store(Request $request) : RedirectResponse
        {

            check_admin_referer('bulk-users');

            if ( ! WP::currentUserCan('edit_users')) {

                throw new AuthorizationException($this->error_message);

            }

            if ( ! $request->has('users') || ! is_array($request->input('users') ) ) {

                $this->response_factory->redirect()->back();

            }

            $users = array_map('intval', Arr::wrap($request->input('users')));

            $reset_count = 0;

            foreach ( $users as $id ) {

                if ( ! WP::currentUserCan('edit_user', $id) ) {

                    throw new AuthorizationException($this->error_message);

                }

                // Dont send reset email to user performing the action
                if ( $id === $request->userId() ) {

                    continue;

                }

                $success = $this->sendResetMail(get_userdata($id));

                if ($success) {
                    $reset_count++;
                }


            }

            return $this->response_factory->redirect()->to($request->path(), 302, [
                'reset_count' => $reset_count,
                'update' => 'resetpassword',
            ]);


        }



    }