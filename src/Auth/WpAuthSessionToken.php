<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth;

    use WPEmerge\Support\Arr;

    class WpAuthSessionToken extends \WP_Session_Tokens
    {

        /** @var AuthSessionManager */
        private $session_manager;

        public const wp_auth_key = '_wp_auth_session_content';

        /**
         * @var array
         */
        private $all_sessions_for_user;

        protected function __construct($user_id)
        {

            parent::__construct($user_id);

            global $session_manager;
            global $__request;
            $this->session_manager = $session_manager;
            $this->session_manager->start( $__request, $user_id );
            unset ($GLOBALS['session_manager']);
            unset ($GLOBALS['__request']);

        }

        /**
         * Retrieves all sessions of the user.
         *
         * @return array Sessions of the user.
         * @since 4.0.0
         *
         */
        protected function get_sessions() : array
        {

            if ( is_array($this->all_sessions_for_user ) ) {
                return $this->all_sessions_for_user;
            }

            $sessions = collect($this->session_manager->getAllForUser());
            $sessions = $sessions
                ->map(function (array $payload) {

                    return Arr::get($payload, static::wp_auth_key, []);

                })
                ->all();

            $this->all_sessions_for_user = is_array($sessions) ? $sessions : [];

            return $this->all_sessions_for_user;

        }

        /**
         * Retrieves a session FROM THE CURRENT USER based on its verifier (token hash).
         *
         * @param  string  $verifier  Verifier for the session to retrieve. NOTE:Already hashed.
         *
         * @return array|null The session, or null if it does not exist.
         * @since 4.0.0
         *
         */
        protected function get_session($verifier) : ?array
        {

            $session = $this->get_sessions()[$verifier] ?? null;
            return $session;

        }

        /**
         * Updates a session OF THE CURRENT USER based on its verifier (token hash).
         *
         * Omitting the second argument destroys the session.
         *
         * We can discard the $verifier here. The only way to get a valid session token
         * programmatically is to use @see wp_get_session_token() which parses the current logged in
         * user cookie which will always(?) be equal to the current active Session.
         *
         * @param  string  $verifier  Verifier for the session to update.
         * @param  array  $session  Optional. Session. Omitting this argument destroys the session.
         *
         * @since 4.0.0
         *
         */
        protected function update_session($verifier, $session = null) : void
        {

            if ( ! $session ) {

                $this->session_manager->activeSession()->invalidate();

            }

            $this->session_manager->activeSession()->put(static::wp_auth_key, $session);

        }

        /**
         * Destroys all sessions for this user, except the single session with the given verifier.
         *
         * @param  string  $verifier  Verifier of the session to keep.
         *
         * @since 4.0.0
         *
         */
        protected function destroy_other_sessions($verifier) : void
        {

            $this->session_manager->destroyOthersForUser($verifier, $this->user_id);

        }

        /**
         * Destroys all sessions for the user.
         *
         * @since 4.0.0
         */
        protected function destroy_all_sessions() : void
        {

            $this->session_manager->destroyAllForUser($this->user_id);

        }

        /**
         * Destroys all sessions for all users.
         */
        public static function drop_sessions() : void
        {

            /** @var WpAuthSessionToken $instance */
            $instance = static::get_instance(0);

            $instance->session_manager->destroyAll();

        }

    }
