<?php

add_action('rest_api_init', function () {
    $base = 'test';
    register_rest_route(EGT_API_NAMESPACE . '/v1', $base . '/send', array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'egt_send_test',
        'permission_callback' => 'egt_is_test'
    ));
});

function egt_send_test(WP_REST_Request $request)
{
   return get_option('lost_password_config');
}

function egt_is_test()
{
    return only_admin_user() && WP_DEBUG == true;
}
