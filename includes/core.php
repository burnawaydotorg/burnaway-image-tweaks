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
        'responsive_sizes' => '192, 340, 480, 540, 768, 1000, 1024, 1440, 1920',
        // New URL template settings
        'url_template' => '?width={width}&format={format}&quality={quality}',
        'url_template_cropped' => '?width={width}&height={height}&fit=crop&crop=smart&format={format}&quality={quality}'
    );
    
    $settings = get_option('burnaway_images_settings', array());
    if (!is_array($settings)) {
        $settings = array();
    }
    return wp_parse_args($settings, $defaults);
}

/**
 * Generate a CDN URL using the configured template
 *
 * @param string $base_url The base image URL
 * @param array $params Parameters to inject into the template (width, height, format, quality)
 * @param bool $is_cropped Whether to use the cropped template
 * @return string The final image URL with parameters
 */
function burnaway_images_get_cdn_url($base_url, $params = array(), $is_cropped = false) {
    // Validate inputs
    if (empty($base_url) || !is_string($base_url)) {
        return ''; // Return empty string if base_url is invalid
    }
    
    // Make sure params is an array
    if (!is_array($params)) {
        $params = array();
    }
    
    // Get settings
    $settings = burnaway_images_get_settings();
    
    // Select the appropriate template
    $template = $is_cropped ? 
        (isset($settings['url_template_cropped']) ? $settings['url_template_cropped'] : '?width={width}&height={height}&fit=crop&crop=smart&format={format}&quality={quality}') : 
        (isset($settings['url_template']) ? $settings['url_template'] : '?width={width}&format={format}&quality={quality}');
    
    // Set default values
    $defaults = array(
        'width' => '',
        'height' => '',
        'format' => isset($settings['formats'][0]) ? $settings['formats'][0] : 'auto',
        'quality' => isset($settings['quality']) ? intval($settings['quality']) : 90
    );
    
    // Merge defaults with provided params
    $params = wp_parse_args($params, $defaults);
    
    // Replace tokens in the template
    $query_string = $template;
    foreach ($params as $key => $value) {
        $query_string = str_replace('{' . $key . '}', $value, $query_string);
    }
    
    // Remove any incomplete tokens
    $query_string = preg_replace('/\{[^}]+\}/', '', $query_string);
    
    // Skip if base URL already has parameters
    if (strpos($base_url, '?') !== false) {
        return $base_url;
    }
    
    return $base_url . $query_string;
}

/**
 * Get WordPress upload directory info with error handling
 * 
 * @return array Upload directory information
 */
function burnaway_images_get_upload_dir() {
    $upload_dir = wp_upload_dir();
    
    // Ensure we always return an array with required keys
    if (!is_array($upload_dir)) {
        return array(
            'path' => '',
            'url' => '',
            'basedir' => '',
            'baseurl' => '',
            'error' => true
        );
    }
    
    return $upload_dir;
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