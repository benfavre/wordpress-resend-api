<?php
/*
Plugin Name: Resend Email Plugin
Description: Override wp_mail function to use Resend API for sending emails
Version: 1.0
Author: WBC
*/

require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php'; // Include Resend PHP SDK

if ( defined( 'WP_CLI' ) && WP_CLI ) {
    // use wp_mail directly
    function send_test_email( $args, $assoc_args ) {
        // Get command line arguments
        $to = $assoc_args['to'];
        $subject = isset( $assoc_args['subject'] ) ? $assoc_args['subject'] : 'Test Email from cli';
        $message = isset( $assoc_args['message'] ) ? $assoc_args['message'] : 'This is a test email sent using Resend API.';

        // Send test email using wp_mail
        $result = wp_mail($to, $subject, $message);

        // Output result
        if ($result) {
            WP_CLI::success( "Test email sent successfully to $to" );
        } else {
            WP_CLI::error( "Failed to send test email to $to" );
        }
    }
    // Register WPCLI command
    WP_CLI::add_command( 'send-test-email', 'send_test_email' );
}

// if not defined
if (!function_exists('wp_mail')) {
    function wp_mail( $to, $subject, $message, $headers = '', $attachments = array() ) {
        $resend_key = get_option('resend_key');
        $from_email = get_option('admin_email');
        $from_name = get_option('blogname');
        $resend = Resend::client($resend_key);
        $result = $resend->emails->send([
            'from' => $from_name . ' <' . $from_email . '>',
            'to' => $to,
            'subject' => $subject,
            'html' => $message,
        ]);

        return true;
    }
}

// Hook to admin_menu action to add options page
add_action('admin_menu', 'resend_email_plugin_options_page');

function resend_email_plugin_options_page() {
    // Add a new submenu under Settings
    add_options_page(
        'Resend Email Plugin Settings',
        'Resend Email Plugin',
        'manage_options',
        'resend-email-plugin',
        'resend_email_plugin_options_page_html'
    );
}

function resend_email_plugin_options_page_html() {
    // Check if user has permissions
    if (!current_user_can('manage_options')) {
        return;
    }
    // save all settings at once
    if (isset($_POST['resend_key']) && isset($_POST['admin_email']) && isset($_POST['from_name'])) {
        update_option('resend_key', sanitize_text_field($_POST['resend_key']));
        update_option('admin_email', sanitize_email($_POST['admin_email']));
        update_option('from_name', sanitize_text_field($_POST['from_name']));
        echo '<div class="updated"><p>Settings saved.</p></div>';
    }

    // Display the options form
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form method="post" action="">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="resend_key">Resend Key</label></th>
                    <td><input type="text" id="resend_key" name="resend_key" value="<?php echo esc_attr(get_option('resend_key')); ?>" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="admin_email">Admin Email</label></th>
                    <td><input type="email" id="admin_email" name="admin_email" value="<?php echo esc_attr(get_option('admin_email')); ?>" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="from_name">From Name</label></th>
                    <td><input type="text" id="from_name" name="from_name" value="<?php echo esc_attr(get_option('from_name')); ?>" /></td>
                </tr>
            </table>
            <input type="submit" class="button-primary" value="Save Changes" />
        </form>
    </div>
    <?php
}