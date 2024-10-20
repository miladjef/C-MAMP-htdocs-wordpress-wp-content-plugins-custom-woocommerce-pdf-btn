<?php
/**
 * Plugin Name: Custom WooCommerce Button
 * Plugin URI: https://miladjafarigavzan.ir
 * Description: Adds a custom button with a unique link or PDF to each WooCommerce product page.
 * Version: 1.1
 * Author: Milad Jafari Gavzan
 * Author URI: https://miladjafarigavzan.ir
 * License: GPL-2.0+
 */

// Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

// Load plugin text domain for translations
function custom_woocommerce_button_load_textdomain() {
    load_plugin_textdomain('custom-woocommerce-button', false, basename(dirname(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'custom_woocommerce_button_load_textdomain');

// Add custom fields for button text, link, and file upload on the product edit page
function add_custom_button_fields_metabox() {
    add_meta_box(
        'custom_button_fields',
        __('Custom Button Settings', 'custom-woocommerce-button'),
        'custom_button_fields_callback',
        'product',
        'side'
    );
}
add_action('add_meta_boxes', 'add_custom_button_fields_metabox');

// Display custom fields for button text, link, and file upload in the product editor
function custom_button_fields_callback($post) {
    $button_text = get_post_meta($post->ID, '_custom_button_text', true);
    $button_link = get_post_meta($post->ID, '_custom_button_link', true);
    $button_pdf = get_post_meta($post->ID, '_custom_button_pdf', true);
    $button_type = get_post_meta($post->ID, '_custom_button_type', true); // Radio button value

    ?>
    <p>
        <label for="custom_button_text"><?php _e('Button Text', 'custom-woocommerce-button'); ?></label>
        <input type="text" id="custom_button_text" name="custom_button_text" value="<?php echo esc_attr($button_text); ?>" style="width:100%;" />
    </p>
    <p>
        <label><?php _e('Choose Button Action', 'custom-woocommerce-button'); ?></label><br>
        <input type="radio" id="custom_button_type_link" name="custom_button_type" value="link" <?php checked($button_type, 'link'); ?> onchange="toggleButtonFields();" />
        <label for="custom_button_type_link"><?php _e('Use Custom Link', 'custom-woocommerce-button'); ?></label><br>
        <input type="radio" id="custom_button_type_pdf" name="custom_button_type" value="pdf" <?php checked($button_type, 'pdf'); ?> onchange="toggleButtonFields();" />
        <label for="custom_button_type_pdf"><?php _e('Use PDF Upload', 'custom-woocommerce-button'); ?></label>
    </p>
    <p id="link_field">
        <label for="custom_button_link"><?php _e('Button Link', 'custom-woocommerce-button'); ?></label>
        <input type="text" id="custom_button_link" name="custom_button_link" value="<?php echo esc_attr($button_link); ?>" style="width:100%;" />
    </p>
    <p id="pdf_field">
        <label for="custom_button_pdf"><?php _e('Upload PDF File', 'custom-woocommerce-button'); ?></label>
        <input type="file" id="custom_button_pdf" name="custom_button_pdf" accept="application/pdf" />
        <?php if ($button_pdf) : ?>
            <p><?php _e('Uploaded PDF:', 'custom-woocommerce-button'); ?> <a href="<?php echo esc_url($button_pdf); ?>" target="_blank"><?php _e('View PDF', 'custom-woocommerce-button'); ?></a></p>
        <?php endif; ?>
        <p class="description"><?php _e('Max file size: 5 MB. Only PDF files allowed.', 'custom-woocommerce-button'); ?></p>
    </p>

    <script>
        function toggleButtonFields() {
            let linkField = document.getElementById('link_field');
            let pdfField = document.getElementById('pdf_field');
            let linkRadio = document.getElementById('custom_button_type_link');
            let pdfRadio = document.getElementById('custom_button_type_pdf');

            if (linkRadio.checked) {
                linkField.style.display = 'block';
                pdfField.style.display = 'none';
            } else if (pdfRadio.checked) {
                linkField.style.display = 'none';
                pdfField.style.display = 'block';
            }
        }
        // Call the function on page load
        toggleButtonFields();
    </script>
    <?php
}

// Save custom button text, link, and PDF file
function save_custom_button_fields($post_id) {
    if (isset($_POST['custom_button_text'])) {
        update_post_meta($post_id, '_custom_button_text', sanitize_text_field($_POST['custom_button_text']));
    }
    if (isset($_POST['custom_button_type'])) {
        update_post_meta($post_id, '_custom_button_type', sanitize_text_field($_POST['custom_button_type']));
    }
    if (isset($_POST['custom_button_link'])) {
        update_post_meta($post_id, '_custom_button_link', sanitize_text_field($_POST['custom_button_link']));
    }

    // Handle file upload for PDF
    if (isset($_FILES['custom_button_pdf']) && !empty($_FILES['custom_button_pdf']['name'])) {
        $uploaded_file = $_FILES['custom_button_pdf'];

        if ($uploaded_file['type'] == 'application/pdf' && $uploaded_file['size'] <= 5242880) { // 5 MB limit
            $upload = wp_upload_bits($uploaded_file['name'], null, file_get_contents($uploaded_file['tmp_name']));
            if (!$upload['error']) {
                update_post_meta($post_id, '_custom_button_pdf', $upload['url']);
            }
        } else {
            // Add error message for invalid file type or size
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>' . __('Error: Only PDF files under 5 MB are allowed.', 'custom-woocommerce-button') . '</p></div>';
            });
        }
    }
}
add_action('save_post', 'save_custom_button_fields');

// Add custom button to the product page
function add_custom_button_to_product_page() {
    global $post;
    $button_text = get_post_meta($post->ID, '_custom_button_text', true);
    $button_type = get_post_meta($post->ID, '_custom_button_type', true);
    $button_link = get_post_meta($post->ID, '_custom_button_link', true);
    $button_pdf = get_post_meta($post->ID, '_custom_button_pdf', true);

    if ($button_type === 'link' && $button_link) {
        $button_text = $button_text ? esc_html($button_text) : __('Custom Button', 'custom-woocommerce-button');
        echo '<a href="' . esc_url($button_link) . '" class="button custom-button" target="_blank">' . $button_text . '</a>';
    } elseif ($button_type === 'pdf' && $button_pdf) {
        $button_text = $button_text ? esc_html($button_text) : __('Download PDF', 'custom-woocommerce-button');
        echo '<a href="' . esc_url($button_pdf) . '" class="button custom-button" target="_blank">' . $button_text . '</a>';
    }
}
add_action('woocommerce_single_product_summary', 'add_custom_button_to_product_page', 35);

?>