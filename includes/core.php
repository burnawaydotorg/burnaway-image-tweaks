<?php
/**
 * Core functionality for Burnaway Images
 *
 * Contains the basic plugin settings and utility functions used across the plugin.
 * This file provides the foundation for all other plugin components.
 *
 * @package BurnawayImages
 * @since 2.3.0
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
 * @return array Plugin settings
 */
function burnaway_images_get_settings() {
    $defaults = array(
        'disable_thumbnails' => true,
        'disable_compression' => true,
        'enable_responsive' => true,
        'disable_scaling' => true,
        'enable_lazy_loading' => true,
        'enable_async_decoding' => true,
        'enable_media_replace' => true,
        'quality' => 90,
        'formats' => array('auto'),
        'responsive_sizes' => '192, 340, 480, 540, 768, 1000, 1024, 1440, 1920'
    );
    
    $settings = get_option('burnaway_images_settings', array());
    return wp_parse_args($settings, $defaults);
}

/**
 * Get WordPress upload directory info
 * 
 * @return array Upload directory information
 */
function burnaway_images_get_upload_dir() {
    return wp_upload_dir();
}

/**
 * Get responsive sizes array
 * 
 * @return array Responsive sizes
 */
function burnaway_images_get_responsive_sizes() {
    $settings = burnaway_images_get_settings();
    $sizes_string = isset($settings['responsive_sizes']) ? $settings['responsive_sizes'] : '192, 340, 480, 540, 768, 1000, 1024, 1440, 1920';
    $sizes = array_map('intval', array_map('trim', explode(',', $sizes_string)));
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
 * Get attachment metadata with error handling
 * 
 * @param int $attachment_id Attachment ID
 * @return array|false Attachment metadata or false on failure
 */
function burnaway_images_get_attachment_metadata($attachment_id) {
    return wp_get_attachment_metadata($attachment_id);
}

/**
 * Get original URL for an attachment
 * 
 * @param int $attachment_id Attachment ID
 * @return string Original URL
 */
function burnaway_images_get_original_url($attachment_id) {
    $url = wp_get_attachment_url($attachment_id);
    
    // Handle scaled images
    if (strpos($url, '-scaled.') !== false) {
        $original_url = str_replace('-scaled.', '.', $url);
        $original_path = str_replace(
            burnaway_images_get_upload_dir()['baseurl'], 
            burnaway_images_get_upload_dir()['basedir'], 
            $original_url
        );
        
        if (file_exists($original_path)) {
            return $original_url;
        }
    }
    
    return $url;
}