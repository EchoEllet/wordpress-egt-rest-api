<?php

add_action('rest_api_init', function () {
    $base = 'woocommerce';
    register_rest_route(EGT_API_NAMESPACE . '/v1', $base . '/products', array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'egt_get_products',
        'permission_callback' => '__return_true'
    ));
    register_rest_route(EGT_API_NAMESPACE . '/v1', $base . '/products/(?P<id>\d+)', array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'egt_get_product',
        'permission_callback' => '__return_true'
    ));
    register_rest_route(EGT_API_NAMESPACE . '/v1', $base . '/categories', array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'egt_get_categories',
        'permission_callback' => '__return_true'
    ));
    register_rest_route(EGT_API_NAMESPACE . '/v1', $base . '/categories/(?P<id>\d+)', array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'egt_get_category',
        'permission_callback' => '__return_true'
    ));
    register_rest_route(EGT_API_NAMESPACE . '/v1', $base . '/dashboard', array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'egt_get_dashboard',
        'permission_callback' => '__return_true'
    ));
    register_rest_route(EGT_API_NAMESPACE . '/v1', $base . '/payment-gateways', array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'egt_get_active_payment_gateways',
        'permission_callback' => '__return_true'
    ));
    register_rest_route(EGT_API_NAMESPACE . '/v1', $base . '/checkout-url', array(
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => 'egt_get_checkout_url',
        'permission_callback' => 'only_authenticated_user'
    ));
    register_rest_route(EGT_API_NAMESPACE . '/v1', $base . '/orders', array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'egt_get_customer_orders',
        'permission_callback' => 'only_authenticated_user'
    ));
    register_rest_route(EGT_API_NAMESPACE . '/v1', $base . '/orders', array(
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => 'egt_place_order',
        'permission_callback' => 'woocommerce_is_registration_required'
    ));
    register_rest_route(EGT_API_NAMESPACE . '/v1', $base . '/wishlist', array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'egt_get_wishlist',
        'permission_callback' => '__return_true'
    ));
});

function egt_get_products(WP_REST_Request $request)
{
    $parameters = $request->get_query_params();
    $limit = sanitize_text_field($parameters['limit'] ?? '');
    $page = sanitize_text_field($parameters['page'] ?? '');
    $product_term = sanitize_text_field($parameters['product_term'] ?? '');

    $args = array(
        'status' => 'publish',
        'limit' => 10,
        'page' => 1,
        'orderby' => 'date',
        'order' => 'ASC',
    );

    if (!empty($limit)) {
        if ($limit >= 100) {
            $args['limit'] = 100;
        } else {
            $args['limit'] = $limit;
        }
    }

    if (!empty($page)) {
        $args['page'] = $page;
    }

    if ($product_term == 'featured') {
        $tax_query[] = array(
            'taxonomy' => 'product_visibility',
            'field' => 'name',
            'terms' => 'featured',
            'operator' => 'IN'//'NOT IN'
        );
        $args['tax_query'] = $tax_query;

        $tmp_products = wc_get_products($args);

    } else if ($product_term == 'on_sale') {
        $on_sale_ids = wc_get_product_ids_on_sale();
        $on_sale_products = array();

        foreach ($on_sale_ids as $id) {
            $tmp_product = wc_get_product($id);
            array_push($on_sale_products, $tmp_product);
        }
        $tmp_products = $on_sale_products;
    } else {
        $tmp_products = wc_get_products($args);
    }

    $products = array();
    foreach ($tmp_products as $product) {

        $product = egt_get_product_helper($product);
        array_push($products, $product);
    }
    return new WP_REST_Response($products, 200);
}

function egt_get_product(WP_REST_Request $request)
{
    $parameters = $request->get_url_params();
    $product_id = sanitize_text_field($parameters['id']);

    if (empty($product_id)) {
        return new WP_Error('id_empty', 'Product id is required', array(
            'status' => 400
        ));
    }

    $id = $parameters['id'];
    $tmp = wc_get_product($id);

    if (!$tmp) {
        return new WP_Error('product_not_exists', 'Product is not exists', array(
            'status' => 404
        ));
    }
    $product = egt_get_product_helper($tmp);
    return new WP_REST_Response($product, 200);
}

function egt_get_categories(WP_REST_Request $request)
{
    $taxonomy = 'product_cat';
    $orderby = 'name';
    $show_count = 0;      // 1 for yes, 0 for no
    $pad_counts = 0;      // 1 for yes, 0 for no
    $hierarchical = 1;      // 1 for yes, 0 for no
    $title = '';
    $empty = 0;

    $args = array(
        'taxonomy' => $taxonomy,
        'orderby' => $orderby,
        'show_count' => $show_count,
        'pad_counts' => $pad_counts,
        'hierarchical' => $hierarchical,
        'title_li' => $title,
        'hide_empty' => $empty
    );
    $all_categories = get_categories($args);
    $categories = array();

    foreach ($all_categories as $category) {
        $thumbnail_id = get_term_meta($category->term_id, 'thumbnail_id', true);
        $image = wp_get_attachment_url($thumbnail_id);
        $category->image_url = null;
        if ($image) {
            $category->image_url = $image;
        }
        array_push($categories, $category);
    }

    return new WP_REST_Response($categories, 200);
}

function egt_get_category(WP_REST_Request $request)
{
    $parameters = $request->get_url_params();
    $category_id = sanitize_text_field($parameters['id'] ?? '');

    if (empty($category_id)) {
        return new WP_Error('id_empty', 'Category id is required', array(
            'status' => 400
        ));
    }

    $category = get_term_by('id', $category_id, 'product_cat');

    if (!$category) {
        return new WP_Error('category_not_exists', 'Category is not exists ', array(
            'status' => 404
        ));
    }

    $thumbnail_id = get_term_meta($category->term_id, 'thumbnail_id', true);
    $image = wp_get_attachment_url($thumbnail_id);
    $category->image_url = null;
    if ($image) {
        $category->image_url = $image;
    }

    return new WP_REST_Response($category, 200);
}

function egt_get_dashboard(WP_REST_Request $request)
{
    $response = array();
    $is_user_logged_in = is_user_logged_in();

    $social_links_options = get_option('egt_social_links_options', array(
        'facebook' => '',
        'whatsapp' => '',
        'telegram' => '',
        'twitter' => '',
        'instagram' => '',
        'phone_number' => '',
        'privacy_policy' => '',
        'copyright_text' => '',
        'term_condition' => ''
    ));
    // Social links
    $social = array(
        'facebook' => $social_links_options['facebook'],
        'whatsapp' => $social_links_options['whatsapp'],
        'telegram' => $social_links_options['telegram'],
        'twitter' => $social_links_options['twitter'],
        'instagram' => $social_links_options['instagram'],
        'phone_number' => $social_links_options['phone_number'],
        'privacy_policy' => $social_links_options['privacy_policy'],
        'copyright_text' => $social_links_options['copyright_text'],
        'term_condition' => $social_links_options['term_condition']
    );
    $response['social'] = $social;

    $social_login_options = get_option('egt_social_login', array(
        'google_client_id' => null,
        'facebook_app_id' => null,
        'facebook_secret_key' => null
    ));
    $google_client_id = $social_login_options['google_client_id'];

    $facebook_app_id = $social_login_options['facebook_app_id'];
    $facebook_secret_key = $social_login_options['facebook_secret_key'];

    $google_login_enabled = $google_client_id != null;
    $facebook_login_enabled = $facebook_app_id != null && $facebook_secret_key != null;
    $response['google_login_enabled'] = $google_login_enabled;
    $response['facebook_login_enabled'] = $facebook_login_enabled;

    // is registration required to process checkout
    $response['checkout_is_registration_required'] = WC()->checkout()->is_registration_required();
    // is registration during checkout enabled
    $response['checkout_is_registration_enabled'] = WC()->checkout()->is_registration_enabled();

    // Sliders
    $sliders = array();
    if (class_exists('Dynamic_Featured_image')) {
        global $dynamic_featured_image;
        $default_slider_id = get_option('egt_slider_list_page_id');
        $sliders = $dynamic_featured_image->get_all_featured_images($default_slider_id ?? null);
    }
    $response['sliders'] = $sliders;

    // Currency Symbol
    $currency_symbol = array(
        'currency_symbol' => get_woocommerce_currency_symbol(),
        'currency' => get_woocommerce_currency()
    );
    $response['currency_symbol'] = $currency_symbol;

    // Total order, User
    $response['total_order'] = 0;
    $response['user'] = null;
    if ($is_user_logged_in) {
        // Get User
        $user = wp_get_current_user();

        // Get User Total order
        $customer_orders = wc_get_orders(array(
            'meta_key' => '_customer_user',
            'meta_value' => $user->ID,
            'numberposts' => -1
        ));
        $response['total_order'] = count($customer_orders);

        egt_get_user_meta_helper($user);
        $response['user'] = $user->data;
    }

    // Get Featured Products
    $args = array(
        'limit' => 5
    );
    $tax_query[] = array(
        'taxonomy' => 'product_visibility',
        'field' => 'name',
        'terms' => 'featured',
        'operator' => 'IN',
    );
    $args['tax_query'] = $tax_query;
    $tmp_products = wc_get_products($args);
    $featured_products = array();
    foreach ($tmp_products as $product) {
        array_push($featured_products, egt_get_product_helper($product));
    }
    $response['featured_products'] = $featured_products;

    // Get On-Sale Products
    $on_sale_ids = wc_get_product_ids_on_sale();
    $on_sale_products = array();

    $i = 0;
    foreach ($on_sale_ids as $id) {
        $i++;
        $tmp_product = wc_get_product($id);
        array_push($on_sale_products, egt_get_product_helper($tmp_product));
        if ($i >= 5) {
            break;
        }
    }
    $response['on_sale_products'] = $on_sale_products;

    if (function_exists('tinvwl_get_wishlist_products') && $is_user_logged_in) {
        $response['wishlist'] = tinvwl_get_wishlist_products();
    }

//    $response['cart'] = get_cart;

    return new WP_REST_Response($response, 200);
}

function egt_get_active_payment_gateways(WP_REST_Request $request)
{
    $gateways = WC()->payment_gateways()->get_available_payment_gateways();
    $enabled_gateways = array();

    foreach ($gateways as $gateway) {
        if ($gateway->enabled == 'yes') {
            array_push($enabled_gateways, array(
                'id' => $gateway->id,
                'method_title' => $gateway->method_title,
                'method_description' => $gateway->method_description,
                'icon' => $gateway->icon ?? '',
                'locale' => $gateway->locale ?? '',
                'order_button_text' => $gateway->order_button_text,
                'supports' => $gateway->supports,
            ));
        }
    }

    return new WP_REST_Response($enabled_gateways, 200);
}

function egt_get_checkout_url(WP_REST_Request $request)
{
    $parameters = $request->get_query_params();
    $order_id = sanitize_text_field($parameters['order_id'] ?? '');

    if (empty($order_id)) {
        return new WP_Error('empty_order_id', 'Please enter a valid Order id', array(
            'status' => 400
        ));
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        return new WP_Error('not_exists', 'This is order is not exists', array(
            'status' => 404
        ));
    }

    if ($order->get_user_id() != get_current_user_id()) {
        return new WP_Error('order_not_for_this_account', 'Sorry, but this is not your order', array(
            'status' => 403
        ));
    }

    $checkout_url = $order->get_checkout_payment_url();
    if (!$checkout_url) {
        return '$checkout_url';
    }

    if ($order->has_status('completed')) return new WP_Error('order_completed', 'Order is already completed', array(
        'status' => 403
    ));

    if ($order->has_status('cancelled')) return new WP_Error('order_cancelled', 'Order is cancelled', array(
        'status' => 403
    ));

    if (!$order->has_status('pending')) return new WP_Error('can_not_get_url', 'Can\'t get checkout url with this status \'' . $order->get_status() . '\'', array(
        'status' => 403
    ));

    $response = array(
        'checkout_url' => $checkout_url
    );
    return new WP_REST_Response($response, 200);
}

/**
 * @throws WC_Data_Exception
 */
function egt_place_order(WP_REST_Request $request)
{
    $parameters = $request->get_json_params();
    $response = array();
    $webview = false;
    $line_items = (array)$parameters['line_items'] ?? array();

    $payment_method = sanitize_text_field($parameters['payment_method'] ?? '');
    $payment_method_title = sanitize_text_field($parameters['payment_method_title'] ?? '');
    $customer_notes = sanitize_text_field($parameters['notes'] ?? '');

    $gateways = WC()->payment_gateways()->get_available_payment_gateways();
    $is_supported_payment_gateway = false;
    $is_user_logged_in = is_user_logged_in();

    if (empty($payment_method)) {
        return new WP_Error('empty_payment_method', 'Payment method is required to place order', array(
            'status' => 400
        ));
    }

    if (empty($payment_method_title)) {
        return new WP_Error('empty_payment_method_title', 'Payment method title is required to place order', array(
            'status' => 400
        ));
    }

    if (empty($line_items)) {
        return new WP_Error('items_required', 'Please add the items', array(
            'status' => 400
        ));
    }

    $is_valid_line_items = validate_line_items_order_schema($line_items);
    if (!$is_valid_line_items) {
        return new WP_Error('invalid_line_items', 'Please enter a valid line items', array(
            'status' => 400
        ));
    }

    foreach ($gateways as $key => $gateway) {
        if ($gateway->enabled == 'yes' && $payment_method_title == $gateway->method_title && $payment_method == $key) {
            $is_supported_payment_gateway = true;
        }
    }

    if (!$is_supported_payment_gateway) {
        if ($payment_method == 'webview' && $payment_method_title == 'Webview') {
            $webview = true;
        } else {
            return new WP_Error('unsupported_payment_method', 'Please chose a supported payment method', array(
                'status' => 403
            ));
        }
    }

    $order = wc_create_order();

    if ($is_user_logged_in) {
        $user = wp_get_current_user();
        egt_get_user_meta_helper($user);

        $billing_address = $user->data->billing_address;
        $shipping_address = $user->data->shipping_address;
        $order->set_customer_id($user->ID);
    } else {
        $billing_address = (array)sanitize_text_field($parameters['billing_address'] ?? array());
        $shipping_address = (array)sanitize_text_field($parameters['shipping_address'] ?? array());
    }

    $order->set_address($billing_address, 'billing');
    $order->set_address($shipping_address, 'shipping');

//    if ($is_user_logged_in && class_exists('CoCart')) {}

    // Adding the products
    foreach ($line_items as $item) {

        $product_id = $item['product_id'];
        $quantity = $item['quantity'];

        $product = wc_get_product($product_id);
        if ($product != false && $product != null) {
            $order->add_product($product, $quantity);
        }
    }

    $needs_payment = $order->needs_payment();
    $needs_processing = $order->needs_processing();
    $totals = $order->calculate_totals();

    $order->set_payment_method($payment_method);
    $order->set_payment_method_title($payment_method_title);

    // the same value when creating order using woocommerce rest api (designed for admin)
    // this function need @throws WC_Data_Exception
    $order->set_created_via('rest-api');

    if (!empty($customer_notes)) {
        $order->set_customer_note($customer_notes);
    }
    $order->save();
    if (is_wp_error($order)) {
        $error_code = $order->get_error_code();
        return new WP_Error($error_code, $order->get_error_message($error_code), array(
            'status' => 403
        ));
    }

    $response = $order->get_data();
    if ($webview == true) {
        $response['checkout_url'] = $order->get_checkout_payment_url();
    }

    return new WP_REST_Response($response, 200);
}

function egt_get_customer_orders(WP_REST_Request $request)
{
    $user = wp_get_current_user();
    $response = array();
    $customer_orders = wc_get_orders(array(
        'meta_key' => '_customer_user',
        'meta_value' => $user->ID,
        'numberposts' => -1
    ));
    foreach ($customer_orders as $customer_order) {
        $order = $customer_order->get_data();
        $order['checkout_url'] = '';

        if ($customer_order->has_status('pending')) {
            $order['checkout_url'] = $customer_order->get_checkout_payment_url();
        }

        array_push($response, $order);
    }
    return $response;
}

function egt_get_wishlist(WP_REST_Request $request)
{
    $product_ids = (array)$request->get_param('ids') ?? array();
    $response = array();

    if (empty($product_ids)) return $response;

    $is_valid_ids = validate_products_ids_schema($product_ids);
    if (!$is_valid_ids) {
        return new WP_Error('invalid_products_ids', 'Please enter a valid Products ids', array(
            'status' => 400
        ));
    }

    foreach ($product_ids as $product_id) {
        $tmp_product = wc_get_product($product_id);
        if ($tmp_product != false && $tmp_product != null) {
            $product = egt_get_product_helper($tmp_product);
            array_push($response, $product);
        }
    }

    return new WP_REST_Response($response, 200);
}