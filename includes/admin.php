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
    
    // Register settings sections
    add_settings_section(
        'burnaway_general_section',
        __('General Settings', 'burnaway-images'),
        'burnaway_images_general_section_callback',
        'burnaway-images'
    );
    
    add_settings_section(
        'burnaway_url_templates_section',
        __('CDN URL Templates', 'burnaway-images'),
        'burnaway_images_url_templates_section_callback',
        'burnaway-images'
    );
    
    // General settings fields
    add_settings_field(
        'disable_thumbnails',
        __('Disable Thumbnails', 'burnaway-images'),
        'burnaway_images_checkbox_callback',
        'burnaway-images',
        'burnaway_general_section',
        array(
            'id' => 'disable_thumbnails',
            'desc' => __('Prevent WordPress from generating multiple thumbnail sizes', 'burnaway-images')
        )
    );
    
    add_settings_field(
        'disable_compression',
        __('Disable Compression', 'burnaway-images'),
        'burnaway_images_checkbox_callback',
        'burnaway-images',
        'burnaway_general_section',
        array(
            'id' => 'disable_compression',
            'desc' => __('Prevent WordPress from compressing uploaded images', 'burnaway-images')
        )
    );
    
    add_settings_field(
        'enable_responsive',
        __('Enable Responsive Images', 'burnaway-images'),
        'burnaway_images_checkbox_callback',
        'burnaway-images',
        'burnaway_general_section',
        array(
            'id' => 'enable_responsive',
            'desc' => __('Add responsive image attributes to deliver optimally sized images', 'burnaway-images')
        )
    );
    
    add_settings_field(
        'disable_scaling',
        __('Disable Scaling', 'burnaway-images'),
        'burnaway_images_checkbox_callback',
        'burnaway-images',
        'burnaway_general_section',
        array(
            'id' => 'disable_scaling',
            'desc' => __('Prevent WordPress from scaling large images on upload', 'burnaway-images')
        )
    );
    
    add_settings_field(
        'enable_lazy_loading',
        __('Enable Lazy Loading', 'burnaway-images'),
        'burnaway_images_checkbox_callback',
        'burnaway-images',
        'burnaway_general_section',
        array(
            'id' => 'enable_lazy_loading',
            'desc' => __('Add loading="lazy" attribute to images', 'burnaway-images')
        )
    );
    
    add_settings_field(
        'enable_async_decoding',
        __('Enable Async Decoding', 'burnaway-images'),
        'burnaway_images_checkbox_callback',
        'burnaway-images',
        'burnaway_general_section',
        array(
            'id' => 'enable_async_decoding',
            'desc' => __('Add decoding="async" attribute to images', 'burnaway-images')
        )
    );
    
    add_settings_field(
        'quality',
        __('Image Quality', 'burnaway-images'),
        'burnaway_images_number_callback',
        'burnaway-images',
        'burnaway_general_section',
        array(
            'id' => 'quality',
            'desc' => __('Quality setting for images (1-100)', 'burnaway-images'),
            'min' => 1,
            'max' => 100
        )
    );
    
    add_settings_field(
        'formats',
        __('Image Formats', 'burnaway-images'),
        'burnaway_images_select_callback',
        'burnaway-images',
        'burnaway_general_section',
        array(
            'id' => 'formats',
            'desc' => __('Preferred image format', 'burnaway-images'),
            'options' => array(
                'auto' => __('Auto (recommended)', 'burnaway-images'),
                'webp' => __('WebP', 'burnaway-images'),
                'jpg' => __('JPEG', 'burnaway-images'),
                'png' => __('PNG', 'burnaway-images')
            )
        )
    );
    
    add_settings_field(
        'responsive_sizes',
        __('Responsive Sizes', 'burnaway-images'),
        'burnaway_images_text_callback',
        'burnaway-images',
        'burnaway_general_section',
        array(
            'id' => 'responsive_sizes',
            'desc' => __('Comma-separated list of widths for responsive images', 'burnaway-images')
        )
    );
    
    // URL template fields
    add_settings_field(
        'url_template',
        __('Standard URL Template', 'burnaway-images'),
        'burnaway_images_text_callback',
        'burnaway-images',
        'burnaway_url_templates_section',
        array(
            'id' => 'url_template',
            'desc' => __('Template for standard images. Available tokens: {width}, {format}, {quality}', 'burnaway-images'),
            'placeholder' => '?width={width}&format={format}&quality={quality}'
        )
    );
    
    add_settings_field(
        'url_template_cropped',
        __('Cropped URL Template', 'burnaway-images'),
        'burnaway_images_text_callback',
        'burnaway-images',
        'burnaway_url_templates_section',
        array(
            'id' => 'url_template_cropped',
            'desc' => __('Template for cropped images. Available tokens: {width}, {height}, {format}, {quality}', 'burnaway-images'),
            'placeholder' => '?width={width}&height={height}&fit=crop&crop=smart&format={format}&quality={quality}'
        )
    );
}
add_action('admin_init', 'burnaway_images_admin_init');

/**
 * General section callback
 */
function burnaway_images_general_section_callback() {
    echo '<p>' . __('Configure general image optimization settings.', 'burnaway-images') . '</p>';
}

/**
 * URL templates section callback
 */
function burnaway_images_url_templates_section_callback() {
    echo '<p>' . __('Configure how image URLs are constructed for your CDN.', 'burnaway-images') . '</p>';
    echo '<div class="burnaway-template-help">';
    echo '<h4>' . __('URL Template Tokens', 'burnaway-images') . '</h4>';
    echo '<p>' . __('Use these tokens in your URL templates:', 'burnaway-images') . '</p>';
    echo '<ul>';
    echo '<li><code>{width}</code> - ' . __('The width of the image', 'burnaway-images') . '</li>';
    echo '<li><code>{height}</code> - ' . __('The height of the image (for cropped images)', 'burnaway-images') . '</li>';
    echo '<li><code>{format}</code> - ' . __('The image format (from format settings)', 'burnaway-images') . '</li>';
    echo '<li><code>{quality}</code> - ' . __('The image quality (from quality settings)', 'burnaway-images') . '</li>';
    echo '</ul>';
    echo '<p>' . __('Example: <code>?w={width}&f={format}&q={quality}</code> for a more compact URL format', 'burnaway-images') . '</p>';
    echo '</div>';
}

/**
 * Checkbox field callback
 */
function burnaway_images_checkbox_callback($args) {
    $options = get_option('burnaway_images_settings');
    $id = $args['id'];
    $checked = isset($options[$id]) ? checked($options[$id], true, false) : '';
    
    echo '<input type="checkbox" id="' . esc_attr($id) . '" name="burnaway_images_settings[' . esc_attr($id) . ']" value="1" ' . $checked . '>';
    echo '<label for="' . esc_attr($id) . '">' . esc_html($args['desc']) . '</label>';
}

/**
 * Text field callback
 */
function burnaway_images_text_callback($args) {
    $options = get_option('burnaway_images_settings');
    $id = $args['id'];
    $value = isset($options[$id]) ? $options[$id] : '';
    $placeholder = isset($args['placeholder']) ? 'placeholder="' . esc_attr($args['placeholder']) . '"' : '';
    
    echo '<input type="text" id="' . esc_attr($id) . '" name="burnaway_images_settings[' . esc_attr($id) . ']" value="' . esc_attr($value) . '" class="regular-text" ' . $placeholder . '>';
    echo '<p class="description">' . esc_html($args['desc']) . '</p>';
}

/**
 * Number field callback
 */
function burnaway_images_number_callback($args) {
    $options = get_option('burnaway_images_settings');
    $id = $args['id'];
    $value = isset($options[$id]) ? $options[$id] : '';
    $min = isset($args['min']) ? 'min="' . intval($args['min']) . '"' : '';
    $max = isset($args['max']) ? 'max="' . intval($args['max']) . '"' : '';
    
    echo '<input type="number" id="' . esc_attr($id) . '" name="burnaway_images_settings[' . esc_attr($id) . ']" value="' . esc_attr($value) . '" class="small-text" ' . $min . ' ' . $max . '>';
    echo '<p class="description">' . esc_html($args['desc']) . '</p>';
}

/**
 * Select field callback
 */
function burnaway_images_select_callback($args) {
    $options = get_option('burnaway_images_settings');
    $id = $args['id'];
    $selected = isset($options[$id]) ? $options[$id] : '';
    
    echo '<select id="' . esc_attr($id) . '" name="burnaway_images_settings[' . esc_attr($id) . '][]">';
    foreach ($args['options'] as $value => $label) {
        $is_selected = is_array($selected) && in_array($value, $selected) ? 'selected="selected"' : '';
        echo '<option value="' . esc_attr($value) . '" ' . $is_selected . '>' . esc_html($label) . '</option>';
    }
    echo '</select>';
    echo '<p class="description">' . esc_html($args['desc']) . '</p>';
}

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