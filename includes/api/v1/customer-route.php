<?php

add_action('rest_api_init', function () {
    register_rest_route(EGT_API_NAMESPACE . '/v1', 'auth/register', array(
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => 'egt_register_customer',
        'permission_callback' => '__return_true'
    ));
    register_rest_route(EGT_API_NAMESPACE . '/v1', 'auth/password/lost', array(
        'methods' => WP_REST_Server::ALLMETHODS,
        'callback' => 'egt_lost_password',
        'permission_callback' => '__return_true'
    ));
    register_rest_route(EGT_API_NAMESPACE . '/v1', 'auth/password/change', array(
        'methods' => WP_REST_Server::ALLMETHODS,
        'callback' => 'egt_change_password',
        'permission_callback' => 'only_authenticated_user'
    ));
    // Social login is using custom_auth, the plugin will add a filter to do a custom_auth with jwt plugin
});

function egt_register_customer(WP_REST_Request $request)
{
    $parameters = $request->get_json_params();
    $email = sanitize_text_field($parameters['email'] ?? '');
    $password = sanitize_text_field($parameters['password'] ?? '');
    $get_token = sanitize_text_field($parameters['get_token' ?? false]);
    $response = array();

    if (empty($email)) {
        return new WP_Error(
            'email_required',
            'Email field is required',
            array(
                'status' => 400,
            )
        );
    }

    if (empty($password)) {
        return new WP_Error(
            'password_required',
            'Password field is required',
            array(
                'status' => 400,
            )
        );
    }

    if (!is_email($email)) {
        return new WP_Error(
            'invalid_email',
            'Please enter a valid email',
            array(
                'status' => 400,
            )
        );
    }

    $username = generate_unique_username($email);

    if (isWooActivated()) {
        $user_id = wc_create_new_customer($email, $username, $password);
    } else {
        $user_id = wp_create_user($username, $password, $email);
    }

    if (is_wp_error($user_id)) {
        $error_code = $user_id->get_error_code();
        return new WP_Error(
            $error_code,
            strip_tags($user_id->get_error_message($error_code)),
            array(
                'status' => 403,
            )
        );
    } else {

        $user = get_user_by('id', $user_id);
        $token = '';

        if ($get_token) {
            $request_token = new WP_REST_Request('POST', '/jwt-auth/v1/token');
            $request_token->set_body_params(array(
                'username' => $user->user_email,
                'password' => $password
            ));
            $response_token = rest_do_request($request_token);
            $server_token = rest_get_server();
            $data = $server_token->response_to_data($response_token, false);
            $token = $data['user']->token;
        }

        egt_get_user_meta_helper($user, $token);

        /*$response = array(
            'success' => true,
            'message' => 'User ' . $username . ' Registration was Successful',
            'user' => $user->data
        );*/
        $response['success'] = true;
        $response['message'] = 'User ' . $username . ' Registration was Successful';
        $response['user'] = $user;
    }

    return new WP_REST_Response($response, 200);
}

function egt_lost_password(WP_REST_Request $request)
{
    $username = sanitize_text_field($request->get_param('username'));

    if (empty($username)) {
        return new WP_Error(
            'empty_username',
            'Username field is required',
            array('status' => 400)
        );
    }

    if (!is_email($username)) {
        $user = get_user_by('login', $username);
    } else {
        $user = get_user_by('email', $username);
    }

    if (!$user) {
        return new WP_Error(
            'unknown_user',
            'Invalid Username',
            array('status' => 403)
        );
    }

    $user_login = $user->user_login;
    $user_email = $user->user_email;
    $key = get_password_reset_key($user);
    $rp_link = '<a href="' . network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user_login), 'login') . '">' . network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user_login), 'login') . '</a>';

    function wpdocs_set_html_mail_content_type()
    {
        return 'text/html';
    }

    function get_msg_subject($lost_password_config)
    {
        return $lost_password_config['email_message_subject'];
    }

    function get_msg_body($lost_password_config)
    {
        return $lost_password_config['email_message_body'];
    }

    $lost_password_config = get_option('egt_lost_password_config', [
        'email_message_subject' => 'تلقينا طلب لاعادة تعيين كلمة مرورك , لاعادة تعيينها الرجاء ادخل على الرابط التالي , اذا لم تطلب تجاهل هذة الرسالة <br>',
        'email_message_body' => 'اعادة تعيين كلمة مرورك'
    ]);

    add_filter('wp_mail_content_type', 'wpdocs_set_html_mail_content_type');
    $is_email_successful = wp_mail($user_email, get_msg_body($lost_password_config), get_msg_subject($lost_password_config) . $rp_link);

    if (!$is_email_successful) {
        return new WP_Error(
            'send_mail_failed',
            'Failed to Send Reset Email Link, Check Your Email Settings, Please Configure It Using plugin like WP Mail SMTP by WPForms',
            array('status' => 500)
        );
    } else {
        $response = array(
            'code' => 'email_sent',
            'message' => 'Reset email link sent successfully'
        );
    }

    remove_filter('wp_mail_content_type', 'wpdocs_set_html_mail_content_type');

    return new WP_REST_Response($response, 200);
}

function egt_change_password(WP_REST_Request $request)
{
    $old_password = sanitize_text_field($request->get_param('old_password'));
    $password = sanitize_text_field($request->get_param('password'));
    if (empty($old_password) || empty($password)) {
        return new WP_Error('password_required', 'Please enter your old and new password to change the password', array(
            'status' => 400
        ));
    }
    if ($old_password == $password) {
        return new WP_Error('same_password', 'The new password and the old password should be not the same', array(
            'status' => 401
        ));
    }
    $user = wp_authenticate(wp_get_current_user()->user_email, $old_password);
    if (is_wp_error($user)) {
        $error_code = $user->get_error_code();
        return new WP_Error($error_code, strip_tags($user->get_error_message($error_code)), array(
            'status' => 403
        ));
    }
    wp_set_password($password, $user->ID);
    return new WP_REST_Response(array(
        'code' => 'password_changed',
        'message' => 'Your password has been changed!'
    ), 200);
}

function egt_social_login($custom_auth_error, $username, $password, $custom_auth)
{
    $social_login_options = get_option('egt_social_login', array(
        'google_client_id' => null,
        'facebook_app_id' => null,
        'facebook_secret_key' => null
    ));

    if ($custom_auth == 'google') {

        $google_client_id = $social_login_options['google_client_id'];
        if ($google_client_id == null) {
            return new WP_Error('not_configured', 'We did n\'t setup google login', array(
                'status' => 500
            ));
        }

        $error = false;
        $id_token = $password;

        if (empty($id_token)) {
            return new WP_Error(
                'id_token_required',
                'Id Token is required',
                array(
                    'status' => 400
                )
            );
        }

        $validation = egt_post_request(array(
            'url' => 'https://oauth2.googleapis.com/tokeninfo',
            'body' => array(
                'id_token' => $id_token
            )
        ));

        if ($validation['code'] != 200) {
            return new WP_Error('invalid_id_token');
        }

        $payload = $validation['body'];
        $error = false;

        // // Specify the CLIENT_ID of the app that accesses the backend
        // $client = new Google_Client(['client_id' => $google_client_id]);
        // try {
        //     $payload = $client->verifyIdToken($id_token);
        //     if ($payload) {
        //         $google_user_id = $payload['sub'];
        //     } else {
        //         $error = true;
        //     }
        // } catch (Exception $e) {
        //     $error = true;
        // }

        if ($error) {
            return new WP_Error(
                'invalid_id_token',
                'Invalid id token',
                array(
                    'status' => 400
                )
            );
        }

        // Get data from google account
        $email = $payload['email'];
        $email_verified = $payload['email_verified'];
        $name = $payload['name'];
        $picture = $payload['picture'];
        $given_name = $payload['given_name'];
        $family_name = $payload['family_name'];
        $locale = isset($payload['locale']) ? $payload['locale'] : "";
        $address = array(
            'first_name' => $given_name,
            'last_name' => $family_name,
            'email' => $email
        );


    } else if ($custom_auth == 'facebook') {
        $facebook_app_id = $social_login_options['facebook_app_id'];
        $facebook_secret_key = $social_login_options['facebook_secret_key'];
        if ($facebook_app_id == null || $facebook_secret_key == null) {
            return new WP_Error('not_configured', 'We did n\'t setup facebook login', array(
                'status' => 500
            ));
        }
        // $fb = new \Facebook\Facebook(array(
        //     'app_id' => $facebook_app_id,
        //     'app_secret' => $facebook_secret_key,
        //     'default_graph_version' => 'v2.10',
        // ));
        // return $fb;
    } else {
        return new WP_Error('unknown_provider', 'Please enter a valid custom_auth provider', array(
            'status' => 400
        ));
    }

    // After cheek id_token/access_token successfully
    // Get user data

    // Check if the user exists in database
    $user = get_user_by('email', $email);

    // if not, create new user
    if (!$user) {

        // with random password
        $username = generate_unique_username($given_name);
        $user_password = wp_generate_password();

        if (isWooActivated()) {
            // Check if woocommerce plugin is activated
            $user_id = wc_create_new_customer($email, $username, $user_password);
        } else {
            $user_id = wp_create_user($username, $user_password, $email);
        }

        if (is_wp_error($user_id)) {
            $error_code = $user_id->get_error_code();
            return new WP_Error($error_code, strip_tags($user_id->get_error_message($error_code)), array(
                'status' => 403,
            ));
        }

        egt_update_user_data($user_id, $address, $locale);

//            $user = get_user_by('email', $email);
        return new WP_User($user_id);

    }

    return $user;

}

function egt_update_user_data($user_id, array $address, string $locale)
{
    update_user_meta($user_id, "billing_first_name", $address['first_name']);
    update_user_meta($user_id, "billing_last_name", $address['last_name']);
    update_user_meta($user_id, "billing_email", $address['email']);

    update_user_meta($user_id, "shipping_first_name", $address['first_name']);
    update_user_meta($user_id, "shipping_last_name", $address['last_name']);

    update_user_meta($user_id, 'first_name', trim($address['first_name']));
    update_user_meta($user_id, 'last_name', trim($address['last_name']));
    if (!empty($locale)) {
        update_user_meta($user_id, 'locale', $locale);
    }
}

function egt_edit_jwt_auth_valid_credential_response($response, WP_User $user)
{
    $token = $response['data']['token'];
    /*        $first_name = $response['data']['firstName'];
            $last_name = $response['data']['lastName'];
            $user->data->first_name = $first_name;
            $user->data->last_name = $last_name;
            $user->data->roles = $user->roles;
            $user->data->token = $token;
            unset($user->user_pass);
            unset($user->user_activation_key);*/
    egt_get_user_meta_helper($user, $token);
    unset($response['data']);
    $response['user'] = $user->data;
    return $response;
}
