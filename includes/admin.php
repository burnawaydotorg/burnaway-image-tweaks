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
 * Register admin menu and settings
 */
function burnaway_images_admin_init() {
    // Add submenu to Media menu
    add_action('admin_menu', 'burnaway_images_add_menu');
    
    // Register plugin settings
    add_action('admin_init', 'burnaway_images_register_settings');
}
add_action('plugins_loaded', 'burnaway_images_admin_init');

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
 * Settings page content
 * 
 * Renders the admin settings page with all options and help information.
 *
 * @since 2.0.0
 */
function burnaway_images_settings_page() {
    // Get current settings
    $settings = get_option('burnaway_images_settings');
    
    // Ensure responsive_sizes is available for the form
    if (!isset($settings['responsive_sizes'])) {
        $settings['responsive_sizes'] = '192, 340, 480, 540, 768, 1000, 1024, 1440, 1920';
    }
    ?>
    <div class="wrap">
        <h1>Burnaway Images Settings</h1>
        
        <form method="post" action="options.php">
            <?php settings_fields('burnaway_images_options'); ?>
            
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Image Processing</th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><span>Image Processing</span></legend>
                            
                            <label for="disable_thumbnails">
                                <input name="burnaway_images_settings[disable_thumbnails]" type="checkbox" id="disable_thumbnails" value="1" <?php checked('1', isset($settings['disable_thumbnails']) ? $settings['disable_thumbnails'] : false); ?>>
                                Disable WordPress default image sizes
                            </label><br>
                            
                            <label for="disable_compression">
                                <input name="burnaway_images_settings[disable_compression]" type="checkbox" id="disable_compression" value="1" <?php checked('1', isset($settings['disable_compression']) ? $settings['disable_compression'] : false); ?>>
                                Disable WordPress image compression (use original quality)
                            </label><br>
                            
                            <label for="enable_responsive">
                                <input name="burnaway_images_settings[enable_responsive]" type="checkbox" id="enable_responsive" value="1" <?php checked('1', isset($settings['enable_responsive']) ? $settings['enable_responsive'] : false); ?>>
                                Enable Fastly-powered responsive images
                            </label><br>
                            
                            <label for="disable_scaling">
                                <input name="burnaway_images_settings[disable_scaling]" type="checkbox" id="disable_scaling" value="1" <?php checked('1', isset($settings['disable_scaling']) ? $settings['disable_scaling'] : false); ?>>
                                Disable WordPress image scaling (use original images)
                            </label><br>
                            
                            <label for="enable_lazy_loading">
                                <input name="burnaway_images_settings[enable_lazy_loading]" type="checkbox" id="enable_lazy_loading" value="1" <?php checked('1', isset($settings['enable_lazy_loading']) ? $settings['enable_lazy_loading'] : false); ?>>
                                Enable lazy loading (loading="lazy")
                            </label><br>

                            <label for="enable_async_decoding">
                                <input name="burnaway_images_settings[enable_async_decoding]" type="checkbox" id="enable_async_decoding" value="1" <?php checked('1', isset($settings['enable_async_decoding']) ? $settings['enable_async_decoding'] : false); ?>>
                                Enable async decoding (decoding="async")
                            </label><br>
                            
                            <p class="description">Control how WordPress processes and serves images.</p>
                        </fieldset>
                    </td>
                </tr>
                
                <tr valign="top">
                    <th scope="row"><label for="image_quality">Image Quality</label></th>
                    <td>
                        <input name="burnaway_images_settings[quality]" type="number" min="1" max="100" id="image_quality" value="<?php echo esc_attr(isset($settings['quality']) ? $settings['quality'] : 90); ?>" class="small-text">
                        <p class="description">Quality parameter for Fastly image optimization (1-100).</p>
                    </td>
                </tr>
                
                <tr valign="top">
                    <th scope="row">Image Format</th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><span>Image Format</span></legend>
                            
                            <label for="format_auto">
                                <input name="burnaway_images_settings[formats][]" type="checkbox" id="format_auto" value="auto" <?php checked(true, isset($settings['formats']) && in_array('auto', $settings['formats'])); ?>>
                                Auto (recommended - let Fastly choose optimal format)
                            </label><br>
                            
                            <label for="format_webp">
                                <input name="burnaway_images_settings[formats][]" type="checkbox" id="format_webp" value="webp" <?php checked(true, isset($settings['formats']) && in_array('webp', $settings['formats'])); ?>>
                                WebP
                            </label><br>
                            
                            <p class="description">Specify image formats for delivery. Auto is recommended.</p>
                        </fieldset>
                    </td>
                </tr>
                
                <tr valign="top">
                    <th scope="row"><label for="responsive_sizes">Responsive Sizes</label></th>
                    <td>
                        <input name="burnaway_images_settings[responsive_sizes]" type="text" id="responsive_sizes" 
                               value="<?php echo esc_attr($settings['responsive_sizes']); ?>"
                               class="regular-text">
                        <p class="description">Comma-separated list of widths (in pixels) for responsive images.</p>
                        <p>Theme-specific widths (192, 340, 540, 768, 1000) are automatically respected when directly requested.</p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(); ?>
        </form>
        
        <div class="card" style="max-width: 800px; margin-top: 20px;">
            <h2>How It Works</h2>
            <p>This plugin optimizes your WordPress images by:</p>
            <ol>
                <li>Preventing WordPress from generating multiple thumbnail sizes</li>
                <li>Disabling WordPress's default image compression</li>
                <li>Using Fastly's image optimization for responsive images</li>
                <li>Ensuring original uploaded images are used instead of scaled versions</li>
                <li>Adding lazy loading and async decoding for better performance</li>
            </ol>
            <p><strong>Note:</strong> This plugin works best when your site is behind Fastly or a similar CDN that supports image optimization parameters.</p>
        </div>
    </div>
    <?php
}