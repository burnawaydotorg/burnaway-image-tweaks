<?php
/**
 * Admin UI Functionality
 *
 * Handles admin interface elements including settings page and options registration.
 *
 * @package BurnawayImages
 * @since 2.2.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize admin functionality
 */
function burnaway_images_admin_init() {
    // Register settings
    register_setting(
        'burnaway_images_options',
        'burnaway_images_settings',
        array(
            'sanitize_callback' => 'burnaway_images_sanitize_settings',
            'default' => array(
                'disable_thumbnails' => true,
                'disable_compression' => true,
                'enable_responsive' => true,
                'disable_scaling' => true,
                'enable_lazy_loading' => true,
                'enable_async_decoding' => true,
                'enable_media_replace' => true,
                'quality' => 90,
                'formats' => array('auto'),
                'responsive_sizes' => '192, 340, 480, 540, 768, 1000, 1024, 1440, 1920',
                'url_template' => '?width={width}&format={format}&quality={quality}',
                'url_template_cropped' => '?width={width}&height={height}&fit=crop&crop=smart&format={format}&quality={quality}'
            )
        )
    );
    
    // Add settings sections and fields
    // ...rest of your settings registration code...
}
add_action('admin_init', 'burnaway_images_admin_init');

/**
 * Register the plugin's settings page
 */
function burnaway_images_register_settings_page() {
    // Make sure to use the proper capability
    add_options_page(
        __('Burnaway Images', 'burnaway-images'),
        __('Burnaway Images', 'burnaway-images'),
        'manage_options', // Use standard WordPress admin capability
        'burnaway-images',
        'burnaway_images_settings_page'
    );
}
add_action('admin_menu', 'burnaway_images_register_settings_page');

/**
 * Create the settings page
 */
function burnaway_images_settings_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }
    
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            // Output security fields
            settings_fields('burnaway_images_options');
            // Output setting sections
            do_settings_sections('burnaway-images');
            // Submit button
            submit_button(__('Save Settings', 'burnaway-images'));
            ?>
        </form>
    </div>
    <?php
}

/**
 * Add admin menu under Media
 * 
 * Creates a submenu page under the WordPress Media menu for configuring
 * the plugin settings.
 *
 * @since 2.0.0
 */
function burnaway_images_add_menu() {
    add_submenu_page(
        'upload.php',                     // Parent slug (Media menu)
        'Burnaway Images Settings',       // Page title
        'Burnaway Images',                // Menu title
        'manage_options',                 // Capability
        'burnaway-images',                // Menu slug
        'burnaway_images_settings_page'   // Function
    );
}

/**
 * Register plugin settings
 * 
 * Registers settings with WordPress and sets up defaults.
 *
 * @since 2.0.0
 */
function burnaway_images_register_settings() {
    register_setting('burnaway_images_options', 'burnaway_images_settings');
    
    // Default settings
    $default_settings = array(
        'disable_thumbnails' => true,
        'disable_compression' => true,
        'enable_responsive' => true,
        'disable_scaling' => true,
        'enable_lazy_loading' => true,
        'enable_async_decoding' => true,
        'quality' => 90,
        'formats' => array('auto'),
        'responsive_sizes' => '192, 340, 480, 540, 768, 1000, 1024, 1440, 1920',
    );
    
    // If settings don't exist, create them
    if (false === get_option('burnaway_images_settings')) {
        add_option('burnaway_images_settings', $default_settings);
    }
}

/**
 * Add URL template settings fields to the admin page
 *
 * @param array $fields Current fields array
 * @return array Updated fields array
 */
function burnaway_images_add_url_template_settings($fields) {
    // Get current fields
    if (!is_array($fields)) {
        $fields = array();
    }
    
    // Add URL template section heading
    $fields[] = array(
        'type' => 'section_start',
        'id' => 'url_templates',
        'title' => __('CDN URL Templates', 'burnaway-images')
    );
    
    // Add standard URL template
    $fields[] = array(
        'type' => 'text',
        'id' => 'url_template',
        'title' => __('Standard URL Template', 'burnaway-images'),
        'desc' => __('Template for standard images. Available tokens: {width}, {format}, {quality}', 'burnaway-images'),
        'placeholder' => '?width={width}&format={format}&quality={quality}'
    );
    
    // Add cropped URL template
    $fields[] = array(
        'type' => 'text',
        'id' => 'url_template_cropped',
        'title' => __('Cropped URL Template', 'burnaway-images'),
        'desc' => __('Template for cropped images. Available tokens: {width}, {height}, {format}, {quality}', 'burnaway-images'),
        'placeholder' => '?width={width}&height={height}&fit=crop&crop=smart&format={format}&quality={quality}'
    );
    
    // Add documentation
    $fields[] = array(
        'type' => 'html',
        'content' => '
        <div class="burnaway-template-help">
            <h4>' . __('URL Template Tokens', 'burnaway-images') . '</h4>
            <p>' . __('Use the following tokens in your URL templates:', 'burnaway-images') . '</p>
            <ul>
                <li><code>{width}</code> - ' . __('The width of the image', 'burnaway-images') . '</li>
                <li><code>{height}</code> - ' . __('The height of the image (for cropped images)', 'burnaway-images') . '</li>
                <li><code>{format}</code> - ' . __('The image format (from format settings)', 'burnaway-images') . '</li>
                <li><code>{quality}</code> - ' . __('The image quality (from quality settings)', 'burnaway-images') . '</li>
            </ul>
            <p>' . __('Example: <code>?w={width}&f={format}&q={quality}</code> for a more compact URL format', 'burnaway-images') . '</p>
        </div>
        '
    );
    
    // Close section
    $fields[] = array(
        'type' => 'section_end'
    );
    
    return $fields;
}
add_filter('burnaway_images_settings_fields', 'burnaway_images_add_url_template_settings');