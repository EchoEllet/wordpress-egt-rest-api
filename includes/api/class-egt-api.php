<?php

if (!class_exists("EGT_Api")) {
    class EGT_Api
    {
        public function __construct()
        {
            $this->load_dependencies();
        }

        private function load_dependencies()
        {
            // WP_REST_Server::READABLE = 'GET'
            // WP_REST_Server::CREATABLE = 'POST'
            // WP_REST_Server::EDITABLE = 'POST, PUT, PATCH'
            // WP_REST_Server::DELETABLE = 'DELETE'
            // WP_REST_Server::ALLMETHODS = 'GET, POST, PUT, PATCH, DELETE'

//            include_once EGT_DIR . 'includes/api/v1/class-egt-api-authentication.php';
            include_once EGT_DIR . 'includes/api/v1/customer-route.php';

            // If Woocommerce Is installed and activated
            if (isWooActivated()) {
                include_once EGT_DIR . 'includes/api/v1/woocommerce-route.php';
                include_once EGT_DIR . 'includes/api/v1/woocommerce-cart-route.php';
            }
            if (isSliderPluginActivated()) {
                include_once EGT_DIR . 'includes/api/v1/slider-route.php';
            }
            include_once EGT_DIR . 'includes/api/v1/plugin-settings-route.php';
            if (WP_DEBUG) {
                include_once EGT_DIR . 'includes/api/v1/test-route.php';
            }
        }
    }
}