<?php
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
$lost_password_config = get_option('egt_lost_password_config', [
    'email_message_subject' => 'تلقينا طلب لاعادة تعيين كلمة مرورك , لاعادة تعيينها الرجاء ادخل على الرابط التالي , اذا لم تطلب تجاهل هذة الرسالة <br>',
    'email_message_body' => 'اعادة تعيين كلمة مرورك'
]);
?>
<link rel="stylesheet" href="<?php echo EGT_DIR_URI . 'assets/css/bootstrap.min.css' ?>">
<!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-U1DAWAznBHeqEIlVSCgzq+c9gqGAJn5c/t99JyeKa9xxaYpSvHU5awsuZVVFIhvj" crossorigin="anonymous"></script> -->
<div class="container-fluid">

    <div id="statusAlert" style="display: none" class="alert alert-success mt-2" role="alert">

    </div>

    <!-- Social Links -->
    <h3 class="mt-2">Social Links</h3>
    <form id="socialLinksForm">
        <input id="wp_rest" value="<?php echo wp_create_nonce('wp_rest') ?>" type="hidden" hidden>
        <div class="mb-2 mt-3">
            <input class="form-control" value="<?php echo $social_links_options['facebook']; ?>" id="facebook" type="text" placeholder="Facebook" required>
        </div>
        <div class="mb-2 mt-3">
            <input class="form-control" value="<?php echo $social_links_options['whatsapp']; ?>" id="whatsapp" type="text" placeholder="Whatsapp" required>
        </div>
        <div class="mb-2 mt-3">
            <input class="form-control" value="<?php echo $social_links_options['telegram']; ?>" id="telegram" type="text" placeholder="Telegram" required>
        </div>
        <div class="mb-2 mt-3">
            <input class="form-control" value="<?php echo $social_links_options['twitter']; ?>" id="twitter" type="text" placeholder="Twitter" required>
        </div>
        <div class="mb-2 mt-3">
            <input class="form-control" value="<?php echo $social_links_options['instagram']; ?>" id="instagram" type="text" placeholder="Instagram" required>
        </div>
        <div class="mb-2 mt-3">
            <input class="form-control" value="<?php echo $social_links_options['phone_number']; ?>" id="phone_number" type="text" placeholder="Phone number" required>
        </div>
        <div class="mb-2 mt-3">
            <input class="form-control" value="<?php echo $social_links_options['privacy_policy']; ?>" id="privacy_policy" type="text" placeholder="Privacy Policy" required>
        </div>
        <div class="mb-2 mt-3">
            <input class="form-control" value="<?php echo $social_links_options['copyright_text']; ?>" id="copyright_text" type="text" placeholder="Copyright Text" required>
        </div>
        <div class="mb-2 mt-3">
            <input class="form-control" value="<?php echo $social_links_options['term_condition']; ?>" id="term_condition" type="text" placeholder="Term condition" required>
        </div>
        <button id="showModal" data-toggle="modal" data-target="#myModal" class="btn btn-primary" type="submit">Save
        </button>
    </form>

    <!-- Sliders List -->
    <h3 class="mt-3">Sliders List</h3>
    <form id="sliderListPageIdForm">
        <div class="mt-2 mb-2">
            <select id="selectSliderListPageId" class="form-select" aria-label="Default select example">
                <?php
                $args = array(
                    'sort_order' => 'asc',
                    'sort_column' => 'post_title',
                    'hierarchical' => 1,
                    'exclude' => '',
                    'include' => '',
                    'meta_key' => '',
                    'meta_value' => '',
                    'authors' => '',
                    'child_of' => 0,
                    'parent' => -1,
                    'exclude_tree' => '',
                    'number' => '',
                    'offset' => 0,
                    'post_type' => 'page',
                    'post_status' => array('publish', 'private')
                );
                $pages = get_pages($args); // get all pages based on supplied args
                $slider_list_page_id = get_option('egt_slider_list_page_id');

                foreach ($pages as $page) { // $pages is array of object
                    if ($slider_list_page_id == $page->ID) {
                        $item_page = '<option selected value="' . $page->ID . '">' . $page->post_title . '</option>';
                    } else {
                        $item_page = '<option value="' . $page->ID . '">' . $page->post_title . '</option>';
                    }

                    echo $item_page;
                }
                ?>
            </select>
        </div>
        <button id="showModal" data-toggle="modal" data-target="#myModal" class="btn btn-primary" type="submit">Save
        </button>
    </form>

    <!-- Social Login -->
    <h3 class="mt-3">Social Options</h3>
    <form id="socialLoginForm">
        <h5 class="mt-2">Google</h5>
        <div class="mt-2 mb-2">
            <input id="googleClientId" type="text" placeholder="Google Client id">
        </div>
        <h5 class="mt-2">Facebook</h5>
        <div class="mt-2 mb-2">
            <input id="facebookAppId" type="text" placeholder="Facebook App Id">
            <input id="facebookSecretKey" type="text" placeholder="Facebook Secret Key">
        </div>
        <button id="showModal" data-toggle="modal" data-target="#myModal" class="btn btn-primary" type="submit">Save
        </button>
    </form>

    <!-- Reset Password Messages -->
    <h3 class="mt-3">Reset Password Messages</h3>
    <form id="resetPasswordMessagesForm">
        <input id="email_message_subject" class="form-control mt-2 mb-2 w-50" value="<?php echo $lost_password_config['email_message_subject'] ?>" type="text" placeholder="Subject">
        <input id="email_message_body" class="form-control mt-2 mb-2 w-50" value="<?php echo $lost_password_config['email_message_body'] ?>" type="text" placeholder="Body">
        <button id="showModal" data-toggle="modal" data-target="#myModal" class="btn btn-primary" type="submit">Save
        </button>
    </form>

    <!-- Modal definition -->
    <div id="myModal" class="modal fade" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-m">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 style="margin:0;">Loading...</h3>
                </div>
                <div class="modal-body">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    var statusAlert = document.getElementById('statusAlert');

    function showAlert(text, error) {
        statusAlert.style.display = 'block';
        if (error) {
            statusAlert.classList.remove('alert-success');
            statusAlert.classList.add('alert-danger');
        } else {
            statusAlert.classList.add('alert-success');
            statusAlert.classList.remove('alert-danger');
        }
        statusAlert.innerHTML = text;
        setTimeout(() => {
            hideAlert();
        }, 4000);
    }

    function hideAlert() {
        statusAlert.style.display = 'none';
    }
    document.getElementById('socialLinksForm').addEventListener('submit', saveSocialLinks);

    function saveSocialLinks(e) {
        e.preventDefault();

        let data = {
            facebook: getInputVal('facebook'),
            whatsapp: getInputVal('whatsapp'),
            telegram: getInputVal('telegram'),
            twitter: getInputVal('twitter'),
            instagram: getInputVal('instagram'),
            phone_number: getInputVal('phone_number'),
            privacy_policy: getInputVal('privacy_policy'),
            copyright_text: getInputVal('copyright_text'),
            term_condition: getInputVal('term_condition')
        }

        let wpNonce = getInputVal('wp_rest');

        fetch(`${document.location.origin}/wp-json/egt-api/v1/plugin-settings/social-links`, {
                method: 'POST',
                body: JSON.stringify(data),
                headers: {
                    "Content-type": "application/json; charset=UTF-8",
                    'X-WP-Nonce': wpNonce
                }
            }).then(response => response.json())
            .then(data => {
                console.log(data);
                showAlert(data.message, false);
            });
    }

    function getInputVal(id) {
        return document.getElementById(id).value;
    }
</script>

<script>
    document.getElementById('sliderListPageIdForm').addEventListener('submit', saveSliderListPage);

    function saveSliderListPage(e) {
        e.preventDefault();

        let data = {
            id: getInputVal('selectSliderListPageId')
        }

        let wpNonce = getInputVal('wp_rest');

        fetch(`${document.location.origin}/wp-json/egt-api/v1/plugin-settings/slider-id`, {
                method: 'POST',
                body: JSON.stringify(data),
                headers: {
                    "Content-type": "application/json; charset=UTF-8",
                    'X-WP-Nonce': wpNonce
                }
            }).then(response => response.json())
            .then(data => {
                console.log(data);
                showAlert(data.message, false);
            });
    }
</script>

<script>
    document.getElementById('socialLoginForm').addEventListener('submit', saveSocialLoginForm);

    function saveSocialLoginForm(e) {
        e.preventDefault();

        let data = {
            google_client_id: getInputVal('googleClientId'),
            facebook_app_id: getInputVal('facebookAppId'),
            facebook_secret_key: getInputVal('facebookSecretKey')
        }

        let wpNonce = getInputVal('wp_rest');

        fetch(`${document.location.origin}/wp-json/egt-api/v1/plugin-settings/social-login`, {
                method: 'POST',
                body: JSON.stringify(data),
                headers: {
                    "Content-type": "application/json; charset=UTF-8",
                    'X-WP-Nonce': wpNonce
                }
            }).then(response => response.json())
            .then(data => {
                console.log(data);
                showAlert(data.message, false);
            });
    }
</script>

<script>
    document.getElementById('resetPasswordMessagesForm').addEventListener('submit', saveResetPasswordMessagesForm);

    function saveResetPasswordMessagesForm(e) {
        e.preventDefault();

        const emailMessageSubject = getInputVal('email_message_subject')
        const emailMessageBody = getInputVal('email_message_body')

        let wpNonce = getInputVal('wp_rest')

        const data = {
            lost_passowrd_config: {
                email_message_subject: emailMessageSubject,
                email_message_body: emailMessageBody
            }
        }

        fetch(`${document.location.origin}/wp-json/egt-api/v1/plugin-settings/reset-password`, {
                method: 'POST',
                body: JSON.stringify(data),
                headers: {
                    "Content-type": "application/json; charset=UTF-8",
                    'X-WP-Nonce': wpNonce
                }
            }).then(response => response.json())
            .then(data => {
                console.log(data);
                showAlert(data.message, false);
            });
    }
</script>