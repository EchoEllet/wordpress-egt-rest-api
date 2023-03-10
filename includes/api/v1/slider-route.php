<?php

add_action('rest_api_init', function () {
    register_rest_route(EGT_API_NAMESPACE . '/v1', '/slider-images', array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'egt_get_slider_images',
        'permission_callback' => '__return_true'
    ));
});

function egt_get_slider_images(WP_REST_Request $request)
{
    global $dynamic_featured_image;
    $slider_id = sanitize_text_field($request->get_param('slider_id'));
    if (empty($slider_id)) $slider_id = get_option('egt_slider_list_page_id');

    $sliders = $dynamic_featured_image->get_featured_images($slider_id);

    return new WP_REST_Response($sliders, 200);
}