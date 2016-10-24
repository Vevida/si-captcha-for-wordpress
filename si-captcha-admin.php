<?php
/*
  SI CAPTCHA Anti-Spam
  http://www.642weather.com/weather/scripts-wordpress-captcha.php
  Adds CAPTCHA anti-spam methods to WordPress on the comment form, registration form, login, or all. This prevents spam from automated bots. Also is WPMU and BuddyPress compatible. <a href="plugins.php?page=si-captcha-for-wordpress/si-captcha.php">Settings</a> | <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=KXJWLPPWZG83S">Donate</a>

  Author: Mike Challis
  http://www.642weather.com/weather/scripts.php
 */

//do not allow direct access
if (strpos(strtolower($_SERVER['SCRIPT_NAME']), strtolower(basename(__FILE__)))) {
    header('HTTP/1.0 403 Forbidden');
    exit('Forbidden');
}

if (isset($_POST['submit'])) {

    if (function_exists('current_user_can') && !current_user_can('manage_options'))
        die(__('You do not have permissions for managing this option', 'si-captcha'));

    check_admin_referer('si-captcha-options_update'); // nonce
    // post changes to the options array
    $optionarray_update = array(
        'si_captcha_perm' => (isset($_POST['si_captcha_perm']) ) ? 'true' : 'false',
        'si_captcha_perm_level' => (trim($_POST['si_captcha_perm_level']) != '' ) ? strip_tags(trim($_POST['si_captcha_perm_level'])) : $si_captcha_option_defaults['si_captcha_perm_level'], // use default if empty
        'si_captcha_comment' => (isset($_POST['si_captcha_comment']) ) ? 'true' : 'false',
        'si_captcha_comment_label_position' => (trim($_POST['si_captcha_comment_label_position']) != '' ) ? strip_tags(trim($_POST['si_captcha_comment_label_position'])) : $si_captcha_option_defaults['si_captcha_comment_label_position'], // use default if empty
        'si_captcha_login' => (isset($_POST['si_captcha_login']) ) ? 'true' : 'false',
        'si_captcha_register' => (isset($_POST['si_captcha_register']) ) ? 'true' : 'false',
        'si_captcha_lostpwd' => (isset($_POST['si_captcha_lostpwd']) ) ? 'true' : 'false',
        'si_captcha_rearrange' => (isset($_POST['si_captcha_rearrange']) ) ? 'true' : 'false',
        'si_captcha_enable_session' => (isset($_POST['si_captcha_enable_session']) ) ? 'true' : 'false',
        'si_captcha_captcha_small' => (isset($_POST['si_captcha_captcha_small']) ) ? 'true' : 'false',
        'si_captcha_honeypot_enable' => (isset($_POST['si_captcha_honeypot_enable']) ) ? 'true' : 'false',
        'si_captcha_aria_required' => (isset($_POST['si_captcha_aria_required']) ) ? 'true' : 'false',
        'si_captcha_required_indicator' => strip_tags(trim($_POST['si_captcha_required_indicator'])),
        'si_captcha_error_spambot' => strip_tags(trim($_POST['si_captcha_error_spambot'])),
        'si_captcha_error_incorrect' => strip_tags(trim($_POST['si_captcha_error_incorrect'])),
        'si_captcha_error_empty' => strip_tags(trim($_POST['si_captcha_error_empty'])),
        'si_captcha_error_token' => strip_tags(trim($_POST['si_captcha_error_token'])),
        'si_captcha_error_error' => strip_tags(trim($_POST['si_captcha_error_error'])),
        'si_captcha_error_unreadable' => strip_tags(trim($_POST['si_captcha_error_unreadable'])),
        'si_captcha_error_cookie' => strip_tags(trim($_POST['si_captcha_error_cookie'])),
        'si_captcha_label_captcha' => strip_tags(trim($_POST['si_captcha_label_captcha'])),
        'si_captcha_tooltip_captcha' => strip_tags(trim($_POST['si_captcha_tooltip_captcha'])),
        'si_captcha_tooltip_refresh' => strip_tags(trim($_POST['si_captcha_tooltip_refresh'])),
    );

    // deal with quotes
    foreach ($optionarray_update as $key => $val) {
        $optionarray_update[$key] = str_replace('&quot;', '"', $val);
    }

    if (isset($_POST['si_captcha_reset_styles'])) {
        // reset styles feature
        $style_resets_arr = array('si_captcha_comment_label_style', 'si_captcha_comment_field_style', 'si_captcha_captcha_div_style', 'si_captcha_captcha_div_style_sm', 'si_captcha_captcha_div_style_m', 'si_captcha_captcha_input_div_style', 'si_captcha_captcha_image_style', 'si_captcha_refresh_image_style');
        foreach ($style_resets_arr as $style_reset) {
            $optionarray_update[$style_reset] = $si_captcha_option_defaults[$style_reset];
        }
    }

    // save updated options to the database
    if ($wpmu == 1)
        update_site_option('si_captcha', $optionarray_update);
    else
        update_option('si_captcha', $optionarray_update);

    // get the options from the database
    if ($wpmu == 1)
        $si_captcha_opt = get_site_option('si_captcha');
    else
        $si_captcha_opt = get_option('si_captcha');

    // strip slashes on get options array
    foreach ($si_captcha_opt as $key => $val) {
        $si_captcha_opt[$key] = $this->si_stripslashes($val);
    }

    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
} // end if (isset($_POST['submit']))
?>
<?php if (!empty($_POST)) : ?>
    <div id="message" class="updated"><p><strong><?php _e('Options saved.', 'si-captcha') ?></strong></p></div>
<?php endif; ?>
<div class="wrap">
    <h2 class="title"><?php _e('SI Captcha Options', 'si-captcha') ?></h2>

    <script type="text/javascript">
        function toggleVisibility(id) {
            var e = document.getElementById(id);
            if (e.style.display == 'block')
                e.style.display = 'none';
            else
                e.style.display = 'block';
        }
    </script>


    <form name="formoptions" action="<?php
    global $wp_version;

// for WP 3.0+ ONLY!
    if ($wpmu == 1 && version_compare($wp_version, '3', '>=') && is_multisite() && is_super_admin())  // wp 3.0 +
        echo admin_url('ms-admin.php?page=si-captcha.php');
    else if ($wpmu == 1)
        echo admin_url('wpmu-admin.php?page=si-captcha.php');
    else
        echo admin_url('options-general.php?page=si-captcha-for-wordpress/si-captcha.php');
    ?>" method="post">
        <input type="hidden" name="action" value="update" />
        <input type="hidden" name="form_type" value="upload_options" />
<?php wp_nonce_field('si-captcha-options_update'); ?>

        <?php
        if (version_compare($wp_version, '3', '<')) { // wp 2 series
            ?>
            <h3><?php _e('Usage', 'si-captcha') ?></h3>

            <p>
                <?php _e('Your theme must have a', 'si-captcha') ?> &lt;?php do_action('comment_form', $post->ID); ?&gt; <?php _e('tag inside your comments.php form. Most themes do.', 'si-captcha');
            echo ' '; ?>
            <?php _e('The best place to locate the tag is before the comment textarea, you may want to move it if it is below the comment textarea, or the captcha image and captcha code entry might display after the submit button.', 'si-captcha') ?>
            </p>
            <?php
        }
        ?>

        <fieldset class="options">

            <table width="100%" cellspacing="2" cellpadding="5" class="form-table">

                <tr>
                    <th scope="row" style="width: 75px;"><?php _e('CAPTCHA:', 'si-captcha') ?></th>
                    <td>

                        <input name="si_captcha_login" id="si_captcha_login" type="checkbox" <?php if ($si_captcha_opt['si_captcha_login'] == 'true') echo ' checked="checked" '; ?> />
                        <label for="si_captcha_login"><?php _e('Enable CAPTCHA on the login form.', 'si-captcha') ?></label>
                        <a style="cursor:pointer;" title="<?php esc_attr_e('Click for Help!', 'si-captcha'); ?>" onclick="toggleVisibility('si_captcha_login_tip');"><?php _e('help', 'si-captcha'); ?></a>
                        <div style="text-align:left; display:none" id="si_captcha_login_tip">
<?php _e('The Login form captcha is not enabled by default because it might be annoying to users. Only enable it if you are having spam problems related to bots automatically logging in.', 'si-captcha') ?>
                        </div>
                        <br />

                        <input name="si_captcha_register" id="si_captcha_register" type="checkbox" <?php if ($si_captcha_opt['si_captcha_register'] == 'true') echo ' checked="checked" '; ?> />
                        <label for="si_captcha_register"><?php _e('Enable CAPTCHA on the register form.', 'si-captcha') ?></label>
                        <a style="cursor:pointer;" title="<?php esc_attr_e('Click for Help!', 'si-captcha'); ?>" onclick="toggleVisibility('si_captcha_register_tip');"><?php _e('help', 'si-captcha'); ?></a>
                        <div style="text-align:left; display:none" id="si_captcha_register_tip">
<?php _e('Prevents automated spam bots by requiring that the user pass a CAPTCHA test before registering.', 'si-captcha') ?>
                        </div>
                        <br />

                        <input name="si_captcha_lostpwd" id="si_captcha_lostpwd" type="checkbox" <?php if ($si_captcha_opt['si_captcha_lostpwd'] == 'true') echo ' checked="checked" '; ?> />
                        <label for="si_captcha_lostpwd"><?php _e('Enable CAPTCHA on the lost password form.', 'si-captcha') ?></label>
                        <a style="cursor:pointer;" title="<?php esc_attr_e('Click for Help!', 'si-captcha'); ?>" onclick="toggleVisibility('si_captcha_lostpwd_tip');"><?php _e('help', 'si-captcha'); ?></a>
                        <div style="text-align:left; display:none" id="si_captcha_lostpwd_tip">
<?php _e('Prevents automated spam bots by requiring that the user pass a CAPTCHA test before lost password request.', 'si-captcha') ?>
                        </div>
                        <br />

                        <input name="si_captcha_comment" id="si_captcha_comment" type="checkbox" <?php if ($si_captcha_opt['si_captcha_comment'] == 'true') echo ' checked="checked" '; ?> />
                        <label for="si_captcha_comment"><?php _e('Enable CAPTCHA on the comment form.', 'si-captcha') ?></label>
                        <a style="cursor:pointer;" title="<?php esc_attr_e('Click for Help!', 'si-captcha'); ?>" onclick="toggleVisibility('si_captcha_enable_tip');"><?php _e('help', 'si-captcha'); ?></a>
                        <div style="text-align:left; display:none" id="si_captcha_enable_tip">
<?php _e('Prevents automated spam bots by requiring that the user pass a CAPTCHA test before posting comments.', 'si-captcha') ?>
                        </div>
                        <br />


                        <input name="si_captcha_perm" id="si_captcha_perm" type="checkbox" <?php if ($si_captcha_opt['si_captcha_perm'] == 'true') echo 'checked="checked"'; ?> />
                        <label name="si_captcha_perm" for="si_captcha_perm"><?php _e('Hide CAPTCHA for', 'si-captcha') ?>
                            <strong><?php _e('registered', 'si-captcha') ?></strong>
<?php _e('users who can:', 'si-captcha') ?></label>
<?php $this->si_captcha_perm_dropdown('si_captcha_perm_level', $si_captcha_opt['si_captcha_perm_level']); ?>
                        <br />

                        <label for="si_captcha_comment_label_position"><?php echo __('CAPTCHA input label position on the comment form:', 'si-captcha'); ?></label>
                        <select id="si_captcha_comment_label_position" name="si_captcha_comment_label_position">
                            <?php
                            $captcha_pos_array = array(
                                'input-label-required' => __('input-label-required', 'si-captcha'), // wp
                                'label-required-input' => __('label-required-input', 'si-captcha'), // bp
                                'label-required-linebreak-input' => __('label-required-linebreak-input', 'si-captcha'), // wp-twenty ten
                                'label-input-required' => __('label-input-required', 'si-captcha'), // suffusion theme on wp
                            );
                            $selected = '';
                            foreach ($captcha_pos_array as $k => $v) {
                                if ($si_captcha_opt['si_captcha_comment_label_position'] == "$k")
                                    $selected = ' selected="selected"';
                                echo '<option value="' . esc_attr($k) . '"' . $selected . '>' . esc_html($v) . '</option>' . "\n";
                                $selected = '';
                            }
                            ?>
                        </select>
                        <a style="cursor:pointer;" title="<?php esc_attr_e('Click for Help!', 'si-captcha'); ?>" onclick="toggleVisibility('si_captcha_comment_label_position_tip');"><?php _e('help', 'si-captcha'); ?></a>
                        <div style="text-align:left; display:none" id="si_captcha_comment_label_position_tip">
<?php _e('Changes position of the CAPTCHA input labels on the comment form. Some themes have different label positions on the comment form. After changing this setting, be sure to view the comments to verify the setting is correct.', 'si-captcha') ?>
                        </div>
                        <br />

                        <input name="si_captcha_rearrange" id="si_captcha_rearrange" type="checkbox" <?php if ($si_captcha_opt['si_captcha_rearrange'] == 'true') echo ' checked="checked" '; ?> />
                        <label for="si_captcha_rearrange"><?php _e('Change the display order of the CAPTCHA input field on the comment form.', 'si-captcha') ?></label>
                        <a style="cursor:pointer;" title="<?php esc_attr_e('Click for Help!', 'si-captcha'); ?>" onclick="toggleVisibility('si_captcha_rearrange_tip');"><?php _e('help', 'si-captcha'); ?></a>
                        <div style="text-align:left; display:none" id="si_captcha_rearrange_tip">
<?php _e('Sometimes the CAPTCHA image and input field are displayed AFTER the submit button on the comment form.', 'si-captcha'); ?>
<?php echo ' ';
_e('Enable this setting and javascript will relocate the button.', 'si-captcha'); ?>
                        </div>
                        <br />

                        <input name="si_captcha_captcha_small" id="si_captcha_captcha_small" type="checkbox" <?php if ($si_captcha_opt['si_captcha_captcha_small'] == 'true') echo ' checked="checked" '; ?> />
                        <label for="si_captcha_captcha_small"><?php echo __('Enable smaller size CAPTCHA image.', 'si-captcha'); ?></label>
                        <a style="cursor:pointer;" title="<?php esc_attr_e('Click for Help!', 'si-captcha'); ?>" onclick="toggleVisibility('si_captcha_captcha_small_tip');"><?php _e('help', 'si-captcha'); ?></a>
                        <div style="text-align:left; display:none" id="si_captcha_captcha_small_tip">
<?php _e('Makes the CAPTCHA image smaller.', 'si-captcha'); ?>
                        </div>
                        <br />

                        <input name="si_captcha_enable_session" id="si_captcha_enable_session" type="checkbox" <?php if ($si_captcha_opt['si_captcha_enable_session'] == 'true') echo ' checked="checked" '; ?> />
                        <label for="si_captcha_enable_session"><?php _e('Enable PHP sessions.', 'si-captcha'); ?></label>
                        <a style="cursor:pointer;" title="<?php esc_attr_e('Click for Help!', 'si-captcha'); ?>" onclick="toggleVisibility('si_captcha_enable_session_tip');"><?php _e('help', 'si-captcha'); ?></a>
                        <div style="text-align:left; display:none" id="si_captcha_enable_session_tip">
                        <?php _e('Enables PHP session handling. Only enable this if you have CAPTCHA token errors. Enable this setting to use PHP sessions for the CAPTCHA. PHP Sessions must be supported by your web host or there may be session errors.', 'si-captcha'); ?>
                        </div>
                        <br />

                        <?php
                        if ($si_captcha_opt['si_captcha_enable_session'] != 'true') {
                            $check_this_dir = untrailingslashit($si_captcha_dir_ns);
                            if (is_writable($check_this_dir)) {
                                //echo '<span style="color: green">OK - Writable</span> ' . substr(sprintf('%o', fileperms($check_this_dir)), -4);
                            } else if (!file_exists($check_this_dir)) {
                                echo '<span style="color: red;">';
                                echo __('There is a problem with the directory', 'si-captcha');
                                echo ' /captcha/cache/. ';
                                echo __('The directory is not found, a <a href="http://codex.wordpress.org/Changing_File_Permissions" target="_blank">permissions</a> problem may have prevented this directory from being created.', 'si-captcha');
                                echo ' ';
                                echo __('Fixing the actual problem is recommended, but you can check this setting on the SI CAPTCHA options page: "Use PHP sessions" and the captcha will work (if PHP sessions are supported by your web host).', 'si-captcha');
                                echo '</span><br />';
                            } else {
                                echo '<span style="color: red;">';
                                echo __('There is a problem with the directory', 'si-captcha') . ' /captcha/cache/. ';
                                echo __('The directory Unwritable (<a href="http://codex.wordpress.org/Changing_File_Permissions" target="_blank">fix permissions</a>)', 'si-captcha') . '. ';
                                echo __('Permissions are: ', 'si-captcha');
                                echo ' ';
                                echo substr(sprintf('%o', fileperms($check_this_dir)), -4);
                                echo ' ';
                                echo __('Fixing this may require assigning 0755 permissions or higher (e.g. 0777 on some hosts. Try 0755 first, because 0777 is sometimes too much and will not work.)', 'si-captcha');
                                echo ' ';
                                echo __('Fixing the actual problem is recommended, but you can check this setting on the SI CAPTCHA options page: "Use PHP sessions" and the captcha will work (if PHP sessions are supported by your web host).', 'si-captcha');
                                echo '</span><br />';
                            }
                        }
                        ?>


                        <input name="si_captcha_honeypot_enable" id="si_captcha_honeypot_enable" type="checkbox" <?php if ($si_captcha_opt['si_captcha_honeypot_enable'] == 'true') echo ' checked="checked" '; ?> />
                        <label for="si_captcha_honeypot_enable"><?php _e('Enable honeypot spambot trap.', 'si-captcha'); ?></label>
                        <a style="cursor:pointer;" title="<?php esc_attr_e('Click for Help!', 'si-captcha'); ?>" onclick="toggleVisibility('si_captcha_honeypot_enable_tip');"><?php _e('help', 'si-captcha'); ?></a>
                        <div style="text-align:left; display:none" id="si_captcha_honeypot_enable_tip">
<?php _e('Enables hidden empty field honyepot trap for spam bots. For best results, do not enable unless you have a spam problem.', 'si-captcha') ?>
                        </div>

                    </td>
                </tr>

                <tr>
                    <th scope="row" style="width: 75px;"><?php _e('Accessibility:', 'si-captcha') ?></th>
                    <td>
                        <input name="si_captcha_aria_required" id="si_captcha_aria_required" type="checkbox" <?php if ($si_captcha_opt['si_captcha_aria_required'] == 'true') echo 'checked="checked"'; ?> />
                        <label name="si_captcha_aria_required" for="si_captcha_aria_required"><?php _e('Enable aria-required tags for screen readers', 'si-captcha') ?>.</label>
                        <a style="cursor:pointer;" title="<?php esc_attr_e('Click for Help!', 'si-captcha'); ?>" onclick="toggleVisibility('si_captcha_aria_required_tip');"><?php _e('help', 'si-captcha'); ?></a>
                        <div style="text-align:left; display:none" id="si_captcha_aria_required_tip">
<?php _e('aria-required is a form input WAI ARIA tag. Screen readers use it to determine which fields are required. Enabling this is good for accessability, but will cause the HTML to fail the W3C Validation (there is no attribute "aria-required"). WAI ARIA attributes are soon to be accepted by the HTML validator, so you can safely ignore the validation error it will cause.', 'si-captcha') ?>
                        </div>
                    </td>
                </tr>

                <tr>
                    <th scope="row" style="width: 75px;"><?php _e('Akismet:', 'si-captcha'); ?></th>
                    <td>
                        <strong><?php _e('Akismet spam prevention status:', 'si-captcha'); ?></strong>

                        <a style="cursor:pointer;" title="<?php esc_attr_e('Click for Help!', 'si-captcha'); ?>" onclick="toggleVisibility('si_captcha_akismet_tip');"><?php _e('help', 'si-captcha'); ?></a>
                        <div style="text-align:left; display:none" id="si_captcha_akismet_tip">
                        <?php _e('Akismet is a WordPress plugin. Akismet will greatly reduce or even completely eliminate the comment and trackback spam you get on your site. If one does happen to get through, simply mark it as "spam" on the moderation screen and Akismet will learn from the mistakes. When Akismet is installed and active, all comment posts will be checked with Akismet to help prevent spam.', 'si-captcha') ?>
                        </div>
                        <br />

                        <?php
                        if (is_callable(array('Akismet', 'verify_key')) || function_exists('akismet_verify_key')) {
                            if (!isset($_POST['si_captcha_akismet_check'])) {
                                echo '<span style="background-color:#99CC99; padding:4px;">' .
                                __('Akismet is installed.', 'si-captcha') . '</strong></span>';
                            }
                            ?>
                            <input name="si_captcha_akismet_check" id="si_captcha_akismet_check" type="checkbox" value="1" />
                            <label for="si_captcha_akismet_check"><?php _e('Check this and click "Update Options" to determine if Akismet key is active.', 'si-captcha'); ?></label>
                            <?php
                            if (isset($_POST['si_captcha_akismet_check'])) {
                                echo '<br/>';
                                $key_status = 'failed';
                                $key = get_option('wordpress_api_key');
                                if (empty($key)) {
                                    $key_status = 'empty';
                                } else {
                                    if (is_callable(array('Akismet', 'verify_key')))
                                        $key_status = Akismet::verify_key($key);  // akismet 3.xx
                                    else
                                        $key_status = akismet_verify_key($key);  // akismet 2.xx
                                }
                                if ($key_status == 'valid') {
                                    echo '<span style="background-color:#99CC99; padding:4px;">' .
                                    __('Akismet is installed and the key is valid. Comment posts will be checked with Akismet to help prevent spam.', 'si-captcha') . '</strong></span>';
                                } else if ($key_status == 'invalid') {
                                    echo '<span style="background-color:#FFE991; padding:4px;">' .
                                    __('Akismet plugin is installed but key needs to be activated.', 'si-captcha') . '</span>';
                                } else if (!empty($key) && $key_status == 'failed') {
                                    echo '<span style="background-color:#FFE991; padding:4px;">' .
                                    __('Akismet plugin is installed but key failed to verify.', 'si-captcha') . '</span>';
                                }
                            }
                            echo '<br/><a href="' . admin_url("options-general.php?page=akismet-key-config") . '">' . __('Configure Akismet', 'si-captcha') . '</a>';
                        } else {
                            echo '<span style="background-color:#FFE991; padding:4px;">' .
                            __('Akismet plugin is not installed or is deactivated.', 'si-captcha') . '</span>';
                        }
                        ?>

                    </td>
                </tr>

            </table>

            <br />
           
           <table cellspacing="2" cellpadding="5" class="form-table">
		   		<tr>
                    <th scope="row" style="width: 75px;"><?php echo __('Text Labels:', 'si-captcha'); ?></th>
                    <td>


                        <strong><?php _e('Change text labels:', 'si-captcha'); ?></strong>
                        <a style="cursor:pointer;" title="<?php echo __('Click for Help!', 'si-captcha'); ?>" onclick="toggleVisibility('si_captcha_labels_tip');"><?php echo __('help', 'si-captcha'); ?></a>
                        <div style="text-align:left; display:none" id="si_captcha_labels_tip">
<?php echo __('Some people wanted to change the text labels. These fields can be filled in to override the standard text labels.', 'si-captcha'); ?>
                        </div>
                        <br />
                        <label for="si_captcha_required_indicator"><?php echo __('Required', 'si-captcha'); ?></label><input name="si_captcha_required_indicator" id="si_captcha_required_indicator" type="text" value="<?php echo esc_attr($si_captcha_opt['si_captcha_required_indicator']); ?>" size="50" /><br />
                        <label for="si_captcha_error_spambot"><?php echo __('Possible spam bot', 'si-captcha'); ?></label><input name="si_captcha_error_spambot" id="si_captcha_error_spambot" type="text" value="<?php echo esc_attr($si_captcha_opt['si_captcha_error_spambot']); ?>" size="50" /><br />
                        <label for="si_captcha_error_incorrect"><?php echo __('Wrong CAPTCHA', 'si-captcha'); ?></label><input name="si_captcha_error_incorrect" id="si_captcha_error_incorrect" type="text" value="<?php echo esc_attr($si_captcha_opt['si_captcha_error_incorrect']); ?>" size="50" /><br />
                        <label for="si_captcha_error_empty"><?php echo __('Empty CAPTCHA', 'si-captcha'); ?></label><input name="si_captcha_error_empty" id="si_captcha_error_empty" type="text" value="<?php echo esc_attr($si_captcha_opt['si_captcha_error_empty']); ?>" size="50" /><br />
                        <label for="si_captcha_error_token"><?php echo __('Missing CAPTCHA token', 'si-captcha'); ?></label><input name="si_captcha_error_token" id="si_captcha_error_token" type="text" value="<?php echo esc_attr($si_captcha_opt['si_captcha_error_token']); ?>" size="50" /><br />
                        <label for="si_captcha_error_unreadable"><?php echo __('Unreadable CAPTCHA token', 'si-captcha'); ?></label><input name="si_captcha_error_unreadable" id="si_captcha_error_unreadable" type="text" value="<?php echo esc_attr($si_captcha_opt['si_captcha_error_unreadable']); ?>" size="50" /><br />
                        <label for="si_captcha_error_cookie"><?php echo __('Unreadable CAPTCHA cookie', 'si-captcha'); ?></label><input name="si_captcha_error_cookie" id="si_captcha_error_cookie" type="text" value="<?php echo esc_attr($si_captcha_opt['si_captcha_error_cookie']); ?>" size="50" /><br />
                        <label for="si_captcha_error_error"><?php echo __('ERROR', 'si-captcha'); ?></label><input name="si_captcha_error_error" id="si_captcha_error_error" type="text" value="<?php echo esc_attr($si_captcha_opt['si_captcha_error_error']); ?>" size="50" /><br />
                        <label for="si_captcha_label_captcha"><?php echo __('CAPTCHA Code', 'si-captcha'); ?></label><input name="si_captcha_label_captcha" id="si_captcha_label_captcha" type="text" value="<?php echo esc_attr($si_captcha_opt['si_captcha_label_captcha']); ?>" size="50" /><br />
                        <label for="si_captcha_tooltip_captcha"><?php echo __('CAPTCHA Image', 'si-captcha'); ?></label><input name="si_captcha_tooltip_captcha" id="si_captcha_tooltip_captcha" type="text" value="<?php echo esc_attr($si_captcha_opt['si_captcha_tooltip_captcha']); ?>" size="50" /><br />
                        <label for="si_captcha_tooltip_refresh"><?php echo __('Refresh Image', 'si-captcha'); ?></label><input name="si_captcha_tooltip_refresh" id="si_captcha_tooltip_refresh" type="text" value="<?php echo esc_attr($si_captcha_opt['si_captcha_tooltip_refresh']); ?>" size="50" />

                    </td>
                </tr>
            </table>

        </fieldset>

        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Update Options', 'si-captcha'); ?>">
        </p>

    </form>
</div>
