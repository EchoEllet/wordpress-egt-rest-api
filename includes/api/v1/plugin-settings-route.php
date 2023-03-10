<?php

add_action('rest_api_init', function () {
    $base = 'plugin-settings';
    register_rest_route(EGT_API_NAMESPACE . '/v1', $base . '/social-links', array(
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => 'egt_set_social_links',
        'permission_callback' => 'only_admin_user'
    ));
    register_rest_route(EGT_API_NAMESPACE . '/v1', $base . '/slider-id', array(
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => 'egt_set_slider_id_page',
        'permission_callback' => 'only_admin_user'
    ));
    register_rest_route(EGT_API_NAMESPACE . '/v1', $base . '/social-login', array(
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => 'egt_set_social_login',
        'permission_callback' => 'only_admin_user'
    ));
    register_rest_route(EGT_API_NAMESPACE . '/v1', $base . '/reset-password', array(
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => 'egt_set_password_configs',
        'permission_callback' => 'only_admin_user'
    ));
});

function egt_set_password_configs(WP_REST_Request $request)
{
    $parameters = $request->get_json_params();
    $response = array();

    $lost_passowrd_config = $parameters['lost_passowrd_config'] ?? array();
    delete_option('egt_lost_password_config');

    $result = update_option('egt_lost_password_config', array(
        'email_message_subject' => $lost_passowrd_config['email_message_subject'],
        'email_message_body' => $lost_passowrd_config['email_message_body']
    ));

    if (!$result) {
        return new WP_Error('error', 'Reset password configurations didn\'t updated', array(
            'status' => 500
        ));
    }

    $response = array(
        'success' => $result,
        'message' => 'Reset password configuration has been updated'
    );

    return $response;
}

function egt_set_social_links(WP_REST_Request $request)
{
    $parameters = $request->get_json_params();
    $response = array();

    $facebook = sanitize_text_field($parameters['facebook'] ?? '');
    $whatsapp = sanitize_text_field($parameters['whatsapp'] ?? '');
    $telegram = sanitize_text_field($parameters['telegram'] ?? '');
    $twitter = sanitize_text_field($parameters['twitter'] ?? '');
    $instagram = sanitize_text_field($parameters['instagram'] ?? '');
    $phone_number = sanitize_text_field($parameters['phone_number'] ?? '');
    $privacy_policy = sanitize_text_field($parameters['privacy_policy'] ?? '');
    $copyright_text = sanitize_text_field($parameters['copyright_text'] ?? '');
    $term_condition = sanitize_text_field($parameters['term_condition'] ?? '');

    delete_option('egt_social_links_options');

    $social_links_options = update_option('egt_social_links_options', array(
        'facebook' => $facebook,
        'whatsapp' => $whatsapp,
        'telegram' => $telegram,
        'twitter' => $twitter,
        'instagram' => $instagram,
        'phone_number' => $phone_number,
        'privacy_policy' => $privacy_policy,
        'copyright_text' => $copyright_text,
        'term_condition' => $term_condition
    ));

    if (!$social_links_options) {
        return new WP_Error('error', 'Links didn\'t updated', array(
            'status' => 500
        ));
    }
    $response = array(
        'success' => true,
        'message' => 'Links updated Successfully!'
    );

    return new WP_REST_Response($response, 200);
}

function egt_set_slider_id_page(WP_REST_Request $request)
{
    $parameters = $request->get_json_params();
    $response = array();

    $slider_page_id = sanitize_text_field($parameters['id'] ?? '');

    if (empty($slider_page_id)) {
        return new WP_Error('slider_page_id_required', 'Please enter a valid slider page id', array(
            'status' => 400
        ));
    }

    if (!get_post_status($slider_page_id)) {
        return new WP_Error('page_not_exists', 'This page is not exists', array(
            'status' => 404
        ));
    }

    $page = get_post($slider_page_id);

    if ($page->post_type != 'page') {
        return new WP_Error('invalid_page', 'This is not a page', array(
            'status' => 403
        ));
    }

    delete_option('egt_slider_list_page_id');
    $update_option = update_option('egt_slider_list_page_id', $slider_page_id);
    if ($update_option) {
        $response = array(
            'success' => true,
            'message' => 'Slider page id updated Successfully!'
        );
    } else {
        return new WP_Error('error', 'Slider page id did\'t updated', array(
            'status' => 500
        ));
    }

    return new WP_REST_Response($response, 200);
}

function egt_set_social_login(WP_REST_Request $request)
{
    $parameters = $request->get_json_params();
    $response = array();

    $google_client_id = sanitize_text_field($parameters['google_client_id'] ?? null);
    $facebook_app_id = sanitize_text_field($parameters['facebook_app_id'] ?? null);
    $facebook_secret_key = sanitize_text_field($parameters['facebook_secret_key'] ?? null);

    delete_option('egt_social_login');
    $social_login_options = update_option('egt_social_login', array(
        'google_client_id' => $google_client_id,
        'facebook_app_id' => $facebook_app_id,
        'facebook_secret_key' => $facebook_secret_key
    ));

    if ($social_login_options) {
        $response = array(
            'success' => true,
            'message' => 'Social Login options updated Successfully!'
        );
    } else {
        return new WP_Error('error', 'Social login options did\'t updated', array(
            'status' => 500
        ));
    }

    return new WP_REST_Response($response, 200);
}
