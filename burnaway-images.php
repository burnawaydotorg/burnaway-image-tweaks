<?php
/**
 * Burnaway Images
 *
 * @package           BurnawayImages
 * @author            Brandon Sheats
 * @copyright         2025 Brandon Sheats
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Burnaway Images
 * Description:       Optimize image delivery with Fastly CDN while disabling WordPress's default image processing for improved performance and quality.
 * Version:           2.1.2
 * Author:            Brandon Sheats
 * Author URI:        https://burnaway.org
 * Text Domain:       burnaway-images
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Requires PHP:      7.2
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('BURNAWAY_IMAGES_VERSION', '2.1.2');
define('BURNAWAY_IMAGES_PATH', plugin_dir_path(__FILE__));
define('BURNAWAY_IMAGES_URL', plugin_dir_url(__FILE__));
define('BURNAWAY_IMAGES_BASENAME', plugin_basename(__FILE__));

// Include debug tools if WP_DEBUG is enabled
if (defined('WP_DEBUG') && WP_DEBUG) {
    require_once BURNAWAY_IMAGES_PATH . 'debug-tools.php';
}

// Include required files
require_once BURNAWAY_IMAGES_PATH . 'includes/core.php';
require_once BURNAWAY_IMAGES_PATH . 'includes/image-processing.php';
require_once BURNAWAY_IMAGES_PATH . 'includes/responsive-images.php';
require_once BURNAWAY_IMAGES_PATH . 'includes/compatibility.php';

// Load admin functionality only in admin area
if (is_admin()) {
    require_once BURNAWAY_IMAGES_PATH . 'includes/admin.php';
}

// Register activation hook
register_activation_hook(__FILE__, 'burnaway_images_activate');

/**
 * Plugin activation callback
 *
 * Sets up default options when the plugin is activated.
 *
 * @since 1.0.0
 * @return void
 */
function burnaway_images_activate() {
    // Set default options if they don't exist
    if (false === get_option('burnaway_images_settings')) {
        add_option('burnaway_images_settings', array(
            'disable_thumbnails' => true,
            'disable_compression' => true,
            'enable_responsive' => true,
            'disable_scaling' => true,
            'enable_lazy_loading' => true,
            'enable_async_decoding' => true,
            'quality' => 90,
            'formats' => array('auto'),
            'responsive_sizes' => '192, 340, 480, 540, 768, 1000, 1024, 1440, 1920',
        ));
    }
}

/**
 * Initialize the plugin by setting up hooks
 */
function burnaway_images_init() {
    // Initialize core functionality
    burnaway_images_core_init();
    
    // Initialize image processing
    burnaway_images_processing_init();
    
    // Set up responsive images
    burnaway_images_responsive_init();
    
    // Set up compatibility with other plugins
    burnaway_images_compatibility_init();
}
add_action('plugins_loaded', 'burnaway_images_init');