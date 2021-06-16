<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth;

    class WpSessionToken extends \WP_Session_Tokens
    {


        protected function get_sessions()
        {
        }

        protected function get_session($verifier)
        {
        }

        protected function update_session($verifier, $session = null)
        {
        }

        protected function destroy_other_sessions($verifier)
        {
        }

        protected function destroy_all_sessions()
        {
        }

    }