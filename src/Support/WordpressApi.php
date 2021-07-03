<?php


    declare(strict_types = 1);


    namespace WPEmerge\Support;


    use WP_User;

    /**
     * @codeCoverageIgnore
     */
    class WordpressApi
    {

        /** @todo needs testing for bedrock installs. But this should return wp/wp-admin normally. */
        public function wpAdminFolder() : string
        {

            $path = parse_url($this->adminUrl(), PHP_URL_PATH );

            return trim($path, '/');

        }

        public function siteName() {

            return get_bloginfo('site_name');

        }

        public function siteUrl() {

            return get_bloginfo('url');

        }

        public function removeFilter(string $tag, $filter, int $priority = 10) : bool
        {

            return remove_filter($tag, $filter, $priority);

        }

        public static function usesTrailingSlashes() : bool
        {

            $permalink_structure = get_option( 'permalink_structure' );

            if ( is_bool($permalink_structure) ) {
                return $permalink_structure;
            }

            return ( '/' === substr( $permalink_structure, -1, 1 ) );

        }

        public function adminEmail() {

            return get_bloginfo('admin_email');

        }

        public function pluginPageHook() : ?string {

            global $pagenow, $plugin_page;

            if ( $plugin_page ) {

                return get_plugin_page_hook( $plugin_page, $pagenow );

            }

            return null;

        }

        public function mail ( $email, string $subject, string $message, array $headers = [], array  $attachments = [] ) {

            return wp_mail($email, $subject, $message, $headers, $attachments);

        }

        public function ajaxUrl() : string
        {

            return WP::wpAdminFolder().DIRECTORY_SEPARATOR.'admin-ajax.php';

        }

        public function isAdmin() : bool
        {

            return is_admin();

        }

        public function checkAdminReferer($action = -1, $query_arg = '_wpnonce') {

            return check_admin_referer($action, $query_arg);

        }

        public function isAdminAjax() : bool
        {

            return wp_doing_ajax();

        }

        public function homeUrl(string $path = '', string $scheme = null) : string
        {

            return home_url($path, $scheme);

        }

        public function adminUrl(string $path = '', string $scheme = 'https') : string
        {

            return admin_url($path, $scheme);

        }

        public function userId() : int
        {

            return get_current_user_id();

        }

        public function currentUser() : WP_User
        {

            return wp_get_current_user();
        }

        public function userIs(string $role) : bool
        {

            $user = $this->currentUser();

            if ( ! empty($user->roles) && is_array($user->roles) && in_array($role, $user->roles)) {
                return true;
            }

            return false;

        }

        public function isUserLoggedIn() : bool
        {

            return is_user_logged_in();

        }

        public function loginUrl(string $redirect_on_login_to = '', bool $force_auth = true) : string
        {
            return wp_login_url($redirect_on_login_to, $force_auth);

        }

        public function logout()
        {

            wp_logout();

        }

        public function pluginPageUrl(string $menu_slug) : string
        {

            return menu_page_url($menu_slug, false);

        }

        public function currentUserCan(string $cap, ...$args) : bool
        {

            return current_user_can($cap, ...$args);

        }

        public function fileHeaderData(string $file, array $default_headers = [], string $context = '') : array
        {

            return get_file_data($file, $default_headers, $context);

        }

        public function addQueryArgs(array $keys, string $url) : string
        {

            return add_query_arg($keys, $url);

        }

        public function addQueryArg(string $key, string $value, string $base_url) : string
        {

            return add_query_arg($key, $value, $base_url);

        }

    }