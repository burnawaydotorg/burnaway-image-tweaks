<?php
/**
 * Core functionality for Burnaway Images
 *
 * Contains the basic plugin settings and utility functions used across the plugin.
 * This file provides the foundation for all other plugin components.
 *
 * @package BurnawayImages
 * @since 2.2.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize core functionality
 *
 * Sets up the core framework for the plugin. Other modules build upon this foundation.
 *
 * @since 2.2.0
 */
function burnaway_images_core_init() {
    // Hook into WordPress to migrate settings if needed (from old plugin name)
    add_action('init', 'burnaway_images_maybe_migrate_settings');
}

/**
 * Migrate settings from older plugin versions
 *
 * Checks for and migrates settings from the previous plugin name if available.
 *
 * @since 2.2.0
 */
function burnaway_images_maybe_migrate_settings() {
    // Check if we need to migrate settings from the old plugin name
    if (false === get_option('burnaway_images_settings') && false !== get_option('burnaway_image_tweaks_settings')) {
        // Copy settings from old name to new name
        $legacy_settings = get_option('burnaway_image_tweaks_settings');
        add_option('burnaway_images_settings', $legacy_settings);
    }
}

/**
 * Get plugin settings with defaults
 *
 * Retrieves the plugin settings from the database or returns defaults.
 * This is a central function used throughout the plugin to ensure
 * consistent access to settings.
 *
 * @since 1.0.0
 * @return array The plugin settings
 */
function burnaway_images_get_settings() {
    $defaults = array(
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
    
    return get_option('burnaway_images_settings', $defaults);
}

/**
 * Get upload directory information
 *
 * Wrapper for wp_upload_dir() for consistency and potential future enhancements.
 *
 * @since 1.0.0
 * @return array Upload directory information
 */
function burnaway_images_get_upload_dir() {
    return wp_upload_dir();
}

/**
 * Get responsive sizes from settings
 *
 * Parses the responsive_sizes setting into an array of integers.
 * Returns sorted, unique width values.
 *
 * @since 2.1.0
 * @return array Array of integer width values
 */
function burnaway_images_get_responsive_sizes() {
    $settings = burnaway_images_get_settings();
    $size_string = isset($settings['responsive_sizes']) ? $settings['responsive_sizes'] : '192, 340, 480, 540, 768, 1000, 1024, 1440, 1920';
    
    // Convert string to array, clean up values
    $sizes = array_map('intval', array_map('trim', explode(',', $size_string)));
    
    // Sort sizes and remove duplicates
    $sizes = array_unique($sizes);
    sort($sizes);
    
    return $sizes;
}

/**
 * Get theme-specific image sizes
 *
 * Returns an array of theme-specific image sizes used in the theme.
 *
 * @since 2.0.0
 * @return array Array of theme size names
 */
function burnaway_images_get_theme_sizes() {
    return array('w192', 'w340', 'w540', 'w768', 'w1000');
}

/**
 * Check if the file exists
 *
 * Simple wrapper for file_exists() for consistency.
 *
 * @since 2.1.0
 * @param string $path File path to check
 * @return bool Whether the file exists
 */
function burnaway_images_file_exists($path) {
    return file_exists($path);
}

/**
 * Get attachment metadata with efficient caching
 *
 * Retrieves attachment metadata with static caching for improved performance.
 *
 * @since 2.1.0
 * @param int $attachment_id Attachment ID
 * @return array|bool Attachment metadata or false if not found
 */
function burnaway_images_get_attachment_metadata($attachment_id) {
    static $cache = array();
    
    if (!isset($cache[$attachment_id])) {
        $cache[$attachment_id] = wp_get_attachment_metadata($attachment_id);
    }
    
    return $cache[$attachment_id];
}

/**
 * Get original image URL with fallback
 *
 * Retrieves the URL to the original image, bypassing WordPress scaled versions when possible.
 * Falls back to scaled version if original is not available.
 *
 * @since 2.2.1
 * @param int $attachment_id Attachment ID
 * @return string URL to the best available image
 */
function burnaway_images_get_original_url($attachment_id) {
    $upload_dir = wp_upload_dir();
    $metadata = wp_get_attachment_metadata($attachment_id);
    
    // First check if there's an original_image (WordPress scaled the image)
    if (isset($metadata['original_image']) && isset($metadata['file'])) {
        $file_dir = dirname($metadata['file']);
        $original_file = $file_dir === '.' ? $metadata['original_image'] : $file_dir . '/' . $metadata['original_image'];
        $original_path = $upload_dir['basedir'] . '/' . $original_file;
        
        // Check if original file actually exists
        if (burnaway_images_file_exists($original_path)) {
            return $upload_dir['baseurl'] . '/' . $original_file;
        }
        
        // Original doesn't exist, log if debugging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Burnaway Images: Original image not found, using scaled version: ' . $original_path);
        }
    }
    
    // Use the regular file path (which might be the scaled version)
    if (isset($metadata['file'])) {
        return $upload_dir['baseurl'] . '/' . $metadata['file'];
    }
    
    // Final fallback to standard function
    return wp_get_attachment_url($attachment_id);
}