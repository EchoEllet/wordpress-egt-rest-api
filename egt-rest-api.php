<?php

/**
 * Plugin Name:       EGT Api
 * Plugin URI:        https://www.egtshop.com
 * Description:       This plugin extends the Wp Rest Api
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Ahmed Riyadh
 * Author URI:        https://ahmedriyadh.me
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       egt-api
 */

defined('ABSPATH') || exit;

// Define global variables

if (!defined('EGT_DIR')) {
    define('EGT_DIR', plugin_dir_path(__FILE__)); // "C:\\xampp\\htdocs\\wp-content\\plugins\\egt-rest-api/"
}

if (!defined('EGT_DIR_URI')) {
    define('EGT_DIR_URI', plugin_dir_url(__FILE__)); // "http://localhost/wp-content/plugins/egt-rest-api/"
}

if (!defined('EGT_API_NAMESPACE')) {
    define('EGT_API_NAMESPACE', 'egt-api');
}

// Define plugins configurations

if (!defined('COCART_WHITE_LABEL')) {
    define('COCART_WHITE_LABEL', true);
}

if (!defined('JWT_AUTH_SECRET_KEY')) {
    define('JWT_AUTH_SECRET_KEY', AUTH_KEY);
}

if (!defined('JWT_AUTH_CORS_ENABLE')) {
    define('JWT_AUTH_CORS_ENABLE', true);
}

// Add admin menu page
add_action('admin_menu', function () {
    add_menu_page('EGT api', 'EGT', 'manage_options', 'egt_api', function () {
        require_once 'templates/admin.php';
    }, 'dashicons-screenoptions', null);
});

// Include helper function
require_once EGT_DIR . 'includes/helper-functions.php';
// Include data validator
require_once EGT_DIR . 'includes/class-validator.php';

// Include custom JWT plugin
require_once EGT_DIR . 'includes/jwt-authentication-for-wp-rest-api/jwt-auth.php';

// Include routes
require_once EGT_DIR . 'includes/api/class-egt-api.php';

// // Include Composer
// require_once EGT_DIR . 'vendor/autoload.php';

require_once EGT_DIR . 'vendor/autoload.php';


add_filter('jwt_auth_whitelist', function ($endpoints) {
    // Use array_unique to remove any duplicate data
    return array_unique(array_merge($endpoints, array(
        '/wp-json/egt-api/v1/*',
        'wp-json/jwt-auth/v1/*'
    )));
});

// Add and modify data in jwt plugin response, If you have another app use the same jwt plugin maybe will have a conflict
add_filter(
    'jwt_auth_valid_credential_response',
    'egt_edit_jwt_auth_valid_credential_response',
    10,
    2
);

// Load Routes
new EGT_Api();

// Do Social Login With Jwt
add_filter(
    'jwt_auth_do_custom_auth',
    'egt_social_login',
    10,
    4
);

// For includes/jwt-authentication-for-wp-rest-api/public/class-jwt-auth-public.php line 97
// to not get error message on the store
add_filter('template_redirect', function () {
    ob_start(null, 0, 0);
});
