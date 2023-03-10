<?php

add_action('rest_api_init', function () {
    register_rest_route('woocommerce/w', '', function() {

    });
});