<?php


    declare(strict_types = 1);


    namespace WPMvc\Auth\Traits;

    use WPMvc\Auth\Mail\ResetPasswordMail;

    trait SendsPasswordResetMails
    {

        private function sendResetMail(\WP_User $user)
        {

            $magic_link = $this->url->signedRoute(
                'auth.reset.password',
                ['query' => ['id' => $user->ID]],
                300,
                true
            );

            return $this->mail->to($user)->send(new ResetPasswordMail($user, $magic_link, 300));


        }

    }