<?php

use phpseclib3\Common\Functions\Strings;

/**
 * $data(url, body, headers)
 * return array with code and body
 * 
 * @param array $data
 * @return array
 */
function egt_post_request(array $data): array
{
    $response = wp_remote_post($data['url'], array(
        'body' => $data['body'],
        'headers' => isset($data['headers']) ? $data['headers'] : array()
    ));

    // body usually return in json format, convert it from json to associative array
    $body_response = (array)json_decode($response['body'], true);

    return array(
        'code' => $response['response']['code'],
        'body' => $body_response
    );
}

/**
 * $data(url, headers)
 * return array with code and body
 *
 * @param array $data
 * @return array
 */
function egt_get_request(array $data): array
{
    $response = wp_remote_get($data['url'], array(
        'headers' => isset($data['headers']) ? $data['headers'] : array()
    ));

    $body_response = (array)json_decode($response['body'], true);

    return array(
        'code' => $response['response']['code'],
        'body' => $body_response
    );
}

function generate_unique_username(string $username)
{
    $username = sanitize_title($username);

    static $i;
    if (null === $i) {
        $i = 1;
    } else {
        $i++;
    }
    if (!username_exists($username)) {
        return $username;
    }
    $new_username = sprintf('%s-%s', $username, $i);
    if (!username_exists($new_username)) {
        return $new_username;
    } else {
        return call_user_func(__FUNCTION__, $username);
    }
}

function isWooActivated(): bool
{
    return in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')));
}

function isSliderPluginActivated(): bool
{
    return class_exists('Dynamic_Featured_image');
}


function egt_get_item_array(array $array, string $key, $default_value = null)
{
    return isset($array[$key]) ? $array[$key] : $default_value;
}

function egt_get_meta_item(array $user_meta, string $key)
{
    return egt_get_item_array($user_meta, $key)[0];
}

function egt_remove_prefix(string $prefix, string $str)
{
    if (substr($str, 0, strlen($prefix)) == $prefix) {
        $str = substr($str, strlen($prefix));
    }
    return $str;
}

function egt_get_user_meta_helper(WP_User $user, string $token = '')
{

    $user->data->roles = $user->roles;
    unset($user->user_pass);
    unset($user->user_activation_key);

    $user_id = $user->ID;
    $user_meta = get_user_meta($user_id);

    $user->data->nickname = egt_get_meta_item($user_meta, 'nickname');
    $user->data->first_name = egt_get_meta_item($user_meta, 'first_name');
    $user->data->last_name = egt_get_meta_item($user_meta, 'last_name');
    $user->data->last_update = egt_get_meta_item($user_meta, 'last_update');
    $user->data->avatar = get_avatar_url($user->ID);
    if (isWooActivated()) {
        $user->data->wc_last_active = egt_get_meta_item($user_meta, 'wc_last_active');
        $billing_address_items = [
            'billing_first_name', 'billing_last_name', 'billing_company', 'billing_address_1',
            'billing_address_2', 'billing_city', 'billing_postcode', 'billing_country',
            'billing_state', 'billing_phone', 'billing_email'
        ];
        $billing_addess = new stdClass();
        foreach ($billing_address_items as $key => $value) {
            $new_value = egt_remove_prefix('billing_', $value);
            $billing_addess->$new_value = egt_get_meta_item($user_meta, $value);
        }
        $user->data->billing_address = $billing_addess;

        $shipping_address_items = [
            'shipping_first_name', 'shipping_last_name', 'shipping_company',
            'shipping_address_1', 'shipping_address_2', 'shipping_city',
            'shipping_postcode', 'shipping_country', 'shipping_state',
            'shipping_phone'
        ];
        $shipping_address = new stdClass();
        foreach ($shipping_address_items as $key => $value) {
            $new_value = egt_remove_prefix('shipping_', $value);
            $shipping_address->$new_value = egt_get_meta_item($user_meta, $value);
        }
        $user->data->shipping_address = $shipping_address;
    }
    if (!empty($token)) {
        $user->data->token = $token;
    }
}

function egt_get_product_helper(WC_Product $product)
{
    $array = array();
    $array['product_id'] = $product->get_id();
    $array['categories'] = $product->get_category_ids();

    $array['name'] = $product->get_name();

    $array['type'] = $product->get_type();
    $array['slug'] = $product->get_slug();
    $array['date_created'] = $product->get_date_created();
    $array['date_modified'] = $product->get_date_modified();
    $array['status'] = $product->get_status();
    $array['featured'] = $product->get_featured();
    $array['on_sale'] = $product->is_on_sale();
    $array['catalog_visibility'] = $product->get_catalog_visibility();
    $array['description'] = $product->get_description();
    $array['short_description'] = $product->get_short_description();
    $array['sku'] = $product->get_sku();

    $array['virtual'] = $product->get_virtual();
    $array['purchasable'] = $product->is_purchasable();
    $array['shipping_required'] = $product->needs_shipping();
    $array['permalink'] = get_permalink($product->get_id());
    $array['price'] = $product->get_price();
    $array['regular_price'] = $product->get_regular_price();
    $array['sale_price'] = $product->get_sale_price();
    $array['price_html'] = $product->get_price_html();
    $array['brand'] = $product->get_attribute('brand');
    $array['size'] = $product->get_attribute('size');
    $array['color'] = $product->get_attribute('color');

    $array['weight_attribute'] = $product->get_attribute('weight');

    $array['tax_status'] = $product->get_tax_status();
    $array['tax_class'] = $product->get_tax_class();
    $array['manage_stock'] = $product->get_manage_stock();
    $array['stock_quantity'] = $product->get_stock_quantity();
    $array['stock_status'] = $product->get_stock_status();
    $array['backorders'] = $product->get_backorders();
    $array['sold_individually'] = $product->get_sold_individually();
    $array['get_purchase_note'] = $product->get_purchase_note();
    $array['shipping_class_id'] = $product->get_shipping_class_id();

    $array['weight'] = $product->get_weight();
    $array['length'] = $product->get_length();
    $array['width'] = $product->get_width();
    $array['height'] = $product->get_height();
    $array['dimensions'] = html_entity_decode($product->get_dimensions());

    // Get Linked Products
    $array['upsell_ids'] = $product->get_upsell_ids();
    $array['cross_sell_ids'] = $product->get_cross_sell_ids();
    $array['parent_id'] = $product->get_parent_id();

    $array['reviews_allowed'] = $product->get_reviews_allowed();
    $array['rating_counts'] = $product->get_rating_counts();
    $array['average_rating'] = $product->get_average_rating();
    $array['review_count'] = $product->get_review_count();

    $thumb = wp_get_attachment_image_src($product->get_image_id(), "thumbnail");
    $full = wp_get_attachment_image_src($product->get_image_id(), "full");
    $array['thumbnail'] = $thumb[0] ?? '';
    $array['full'] = $full[0] ?? '';
    $gallery = array();
    foreach ($product->get_gallery_image_ids() as $img_id) {
        $g = wp_get_attachment_image_src($img_id, "full");
        $gallery[] = $g[0];
    }
    $array['gallery'] = $gallery;


    return $array;
}

function user_not_logged_in_error(): WP_Error
{
    return new WP_Error(
        'not_authenticated',
        'Please login first to access this route',
        array(
            'status' => 401
        )
    );
}

function only_authenticated_user()
{
    if (!is_user_logged_in()) {
        return user_not_logged_in_error();
    } else {
        return true;
    }
}

function woocommerce_is_registration_required()
{
    if (WC()->checkout()->is_registration_required()) {
        if (!is_user_logged_in()) {
            return user_not_logged_in_error();
        } else {
            return true;
        }
    } else {
        return true;
    }
}

function only_admin_user()
{
    if (is_user_logged_in() && current_user_can('manage_options')) {
        return true;
    } else {
        return new WP_Error('no_access', 'You don\'t have access to this resource', array(
            'status' => 403
        ));
    }
}

/**
 * Recursive sanitation for an array
 * @param $array
 *
 * @return mixed
 */
function recursive_sanitize_text_field($array)
{
    foreach ($array as $key => &$value) {
        if (is_array($value)) {
            $value = recursive_sanitize_text_field($value);
        } else {
            $value = sanitize_text_field($value);
        }
    }

    return $array;
}

function validate_line_items_order_schema(array $items): bool
{
    if (is_assoc($items)) return false;
    foreach ($items as $key => $item) {
        if (!is_array($item)) {
            return false;
        } else if (!array_key_exists('product_id', $item)) {
            return false;
        } else if (!is_int($item['product_id'])) {
            return false;
        } else if (!array_key_exists('quantity', $item)) {
            return false;
        } else if (!is_int($item['quantity'])) {
            return false;
        }
    }
    return true;
}

function validate_products_ids_schema(array $items): bool
{
    if (is_assoc($items)) return false;

    foreach ($items as $key => $item) {
        if (!is_int($item)) {
            return false;
        }
    }

    return true;
}

function is_assoc(array $arr): bool
{
    if (array() === $arr) return false;
    return array_keys($arr) !== range(0, count($arr) - 1);
}
