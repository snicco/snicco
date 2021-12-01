<?php /** @noinspection PhpInconsistentReturnPointsInspection */

declare(strict_types=1);

namespace Snicco\Support;

use WP_User;

/**
 * mixin class for ide support.
 *
 * @see \Snicco\Support\WordpressApi
 * @codeCoverageIgnore
 */
class WordpressApiMixin
{
    
    private function __construct()
    {
    }
    
    /**
     * @see get_bloginfo()
     * @see WordpressApi::siteName
     */
    public static function siteName() :string
    {
    }
    
    /**
     * @see remove_filter()
     * @see WordpressApi::removeFilter()
     */
    public static function removeFilter(string $tag, callable $function_to_remove, $priority = 10) :bool
    {
    }
    
    /**
     * @see get_bloginfo()
     * @see WordpressApi::siteUrl
     */
    public static function siteUrl() :string
    {
    }
    
    /**
     * @see get_bloginfo()
     * @see WordpressApi::adminEmail
     */
    public static function adminEmail() :string
    {
    }
    
    /**
     * Check if trailing slashes are used.
     *
     * @see \WP_Rewrite
     * @see WordpressApi::usesTrailingSlashes
     */
    public static function usesTrailingSlashes() :bool
    {
    }
    
    /**
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
    public static function mail($email, string $subject, string $message, array $headers = [], array $attachments = []) :bool
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
     * Create a link to an admin url
     *
     * @param  string  $path
     * @param  string  $scheme
     *
     * @return string
     * @see self_admin_url()
     * @see WordpressApi::adminUrl();
     */
    public static function adminUrl(string $path = '', string $scheme = 'https') :string
    {
    }
    
    /**
     * Get the current user's ID
     *
     * @return int The current user's ID, or 0 if no user is logged in.
     * @see \Snicco\Support\WordpressApi::userId()
     */
    public static function userId() :int
    {
    }
    
    /**
     * Check if the currently signed in user has the given role
     *
     * @param  string  $user_role
     *
     * @see WordpressApi::userIs()
     */
    public static function userIs(string $user_role) :bool
    {
    }
    
    /**
     * Get the current wp user object
     *
     * @see wp_get_current_user()
     * @see WordpressApi::currentUser();
     */
    public static function currentUser() :WP_User
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
     * @see \Snicco\Support\WordpressApi::loginUrl()
     */
    public static function loginUrl(string $redirect_on_login_to = '', bool $force_auth = false) :string
    {
    }
    
    /**
     * Returns whether the current user has the specified capability.
     * Example usage:
     *     currentUserCan( 'edit_posts' );
     *     currentUserCan( 'edit_post', $post->ID );
     *     currentUserCan( 'edit_post_meta', $post->ID, $meta_key );
     *
     * @param  string  $cap  Capability name.
     * @param  mixed  ...$args  Optional further parameters, typically starting with an object
     *     ID.
     *
     * @return bool
     * @see \Snicco\Support\WordpressApi::currentUserCan()
     * @see \current_user_can()
     */
    public static function currentUserCan(string $cap, ...$args) :bool
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
     * @see \Snicco\Support\WordpressApi::fileHeaderData();
     */
    public static function fileHeaderData(string $file, array $default_headers = [], string $context = '') :array
    {
    }
    
    /**
     * Return the name of the admin folder.
     * Default 'wp-admin".
     *
     * @return string
     * @see WordpressApi::wpAdminFolder()
     */
    public static function wpAdminFolder() :string
    {
    }
    
    /**
     * Return the path of the admin ajax endpoint WITHOUT leading slash
     *
     * @return string
     * @see WordpressApi::ajaxUrl()
     */
    public static function ajaxUrl() :string
    {
    }
    
}