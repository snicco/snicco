<?php /** @noinspection PhpInconsistentReturnPointsInspection */


    declare(strict_types = 1);


    namespace WPEmerge\Facade;


    use WP_User;

    /**
     * mixin class for ide support.
     *
     * @see \WPEmerge\Facade\WordpressApi
     *
     * @codeCoverageIgnore
     */
    class WordpressApiMixin
    {

        private function __construct()
        {
        }


        /**
         *
         * @param string|string[] $email
         * @param  string  $subject
         * @param string $message
         *
         * @see wp_mail()
         * @see WordpressApi::plaintTextMail
         */
        public static function plainTextMail($email, string $subject, string $message)
        {

        }

        /**
         *
         * @param  string|string[]  $email
         * @param  string  $subject
         * @param  string  $message
         * @param  array  $headers
         * @param  array  $attachments
         *
         * @return bool
         * @see wp_mail()
         * @see WordpressApi::mail()
         */
        public static function mail($email, string $subject, string $message, array $headers = [], array  $attachments = [] ) :bool
        {

        }

        /**
         * Log out the current user and destroy all auth cookies
         * @see WordpressApi::logout
         */
        public static function logout()
        {
        }

        /**
         * Check if we are in the admin dashboard
         *
         * @return bool
         * @see \WPEmerge\Facade\WordpressApi::isAdmin()
         */
        public static function isAdmin() : bool
        {
        }

        /**
         * Verify a wp-nonce and dies on failure.
         *
         * @param  int|string  $action
         * @param  string  $query_arg
         *
         * @return int|false
         *
         * @see check_admin_referer()
         * @see \WPEmerge\Facade\WordpressApi::checkAdminReferer()
         *
         */
        public static function checkAdminReferer($action = -1, $query_arg = '_wpnonce')
        {
        }

        /**
         * Check if we are doing a request to admin-ajax.php
         *
         * @return bool
         * @see \WPEmerge\Facade\WordpressApi::isAdminAjax()
         */
        public static function isAdminAjax() : bool
        {
        }

        /**
         * Return the current admin page if it was added with
         * add_menu_page() or add_submenu_page()
         *
         * @see get_plugin_page_hook()
         * @see WordpressApi::pluginPageHook
         */
        public static function pluginPageHook() :?string
        {

        }

        /**
         *
         *
         * @param  string  $path  Optional. FilePath relative to the home URL. Default empty.
         * @param  string|null  $scheme  Optional. Scheme to give the home URL context. Accepts
         *                            'http', 'https', 'relative', 'rest', or null. Default null.
         *
         * @return string
         * @see \WPEmerge\Facade\WordpressApi::homeUrl()
         */
        public static function homeUrl(string $path = '', string $scheme = null) : string
        {
        }

        /**
         *
         * Create a link to an admin url
         *
         * @param  string  $path
         * @param  string  $scheme
         *
         * @return string
         *
         * @see self_admin_url()
         * @see WordpressApi::adminUrl();
         *
         */
        public static function adminUrl(string $path = '', string $scheme = 'https') : string
        {
        }

        /**
         * Get the current user's ID
         *
         * @return int The current user's ID, or 0 if no user is logged in.
         * @see \WPEmerge\Facade\WordpressApi::userId()
         */
        public static function userId() : int
        {
        }

        /**
         *
         * Check if the currently signed in user has the given role
         *
         * @param  string  $user_role
         *
         * @see WordpressApi::userIs()
         */
        public static function userIs(string $user_role) : bool
        {
        }

        /**
         *
         * Get the current wp user object
         *
         * @see wp_get_current_user()
         * @see WordpressApi::currentUser();
         */
        public static function currentUser() : WP_User
        {


        }

        /**
         * Determines whether the current visitor is a logged in user.
         *
         * @return bool True if user is logged in, false if not logged in.
         * @see WordpressApi::isUserLoggedIn()
         */
        public static function isUserLoggedIn() :bool
        {
        }

        /**
         * Retrieves the login URL.
         *
         * @param  string  $redirect_on_login_to  FilePath to redirect to on log in.
         * @param  bool  $force_auth  Whether to force reauthorization, even if a cookie is present.
         *                             Default false.
         *
         * @return string The login URL. Not HTML-encoded.
         * @see \WPEmerge\Facade\WordpressApi::loginUrl()
         *
         */
        public static function loginUrl(string $redirect_on_login_to = '', bool $force_auth = false) : string
        {
        }

        /**
         * Returns whether the current user has the specified capability.
         *
         * Example usage:
         *
         *     currentUserCan( 'edit_posts' );
         *     currentUserCan( 'edit_post', $post->ID );
         *     currentUserCan( 'edit_post_meta', $post->ID, $meta_key );
         *
         * @param  string  $cap  Capability name.
         * @param  mixed  ...$args  Optional further parameters, typically starting with an object
         *     ID.
         *
         * @return bool
         * @see \WPEmerge\Facade\WordpressApi::currentUserCan()
         * @see \current_user_can()
         *
         *
         */
        public static function currentUserCan(string $cap, ...$args) : bool
        {
        }

        /**
         * @param  string  $file  Absolute path to the file.
         * @param  array  $default_headers  List of headers, in the format `array( 'HeaderKey' =>
         *     'Header Name' )`.
         * @param  string  $context  Optional. If specified adds filter hook
         *     {@see 'extra_$context_headers'}.
         *
         * @return string[]
         * @see \get_file_data()
         * @see \WPEmerge\Facade\WordpressApi::fileHeaderData();
         */
        public static function fileHeaderData(string $file, array $default_headers = [], string $context = '') : array
        {
        }

        /**
         *
         * Create a url to a plugin menu page
         *
         * @param  string  $menu_slug
         *
         * @return string
         * @see WordpressApi::pluginPageUrl()
         * @see menu_page_url()
         */
        public static function pluginPageUrl(string $menu_slug) : string
        {
        }

        /**
         * Return the name of the admin folder.
         * Default 'wp-admin".
         *
         * @return string
         * @see WordpressApi::wpAdminFolder()
         */
        public static function wpAdminFolder() : string
        {
        }

        /**
         * Return the path of the admin ajax endpoint WITHOUT leading slash
         *
         * @return string
         * @see WordpressApi::ajaxUrl()
         */
        public static function ajaxUrl() : string{}

        /**
         *
         * @param  array  $keys  associative array of [param => value ] pairs
         * @param  string  $url  Url to append to.
         *
         * @return string
         * @see WordpressApi::addQueryArgs()
         */
        public static function addQueryArgs(array $keys, string $url) : string
        {
        }

        /**
         *
         * Append a single query var to an URL.
         *
         * @param  string  $key  parameter key
         * @param  string  $value  parameter value
         * @param  string  $base_url  URL to append to
         *
         * @return string
         * @see WordpressApi::addQueryArg()
         */
        public static function addQueryArg(string $key, string $value, string $base_url) : string
        {
        }

    }