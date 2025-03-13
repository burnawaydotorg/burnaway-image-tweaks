<?php
/**
 * Compatibility Functionality
 *
 * Handles compatibility with other plugins, specifically ShortPixel.
 * Ensures Burnaway Images plays nicely with other optimization tools.
 *
 * @package BurnawayImages
 * @since 2.2.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize compatibility features
 *
 * @since 2.2.0
 */
function burnaway_images_compatibility_init() {
    // Set up ShortPixel compatibility
    add_action('plugins_loaded', 'burnaway_images_shortpixel_compatibility');
}

/**
 * Check if the current request is from ShortPixel
 * 
 * Uses efficient detection methods to determine if the current operation
 * is being performed by ShortPixel.
 *
 * @since 2.1.0
 * @return bool Whether the request is from ShortPixel
 */
function burnaway_images_is_shortpixel_request() {
    static $is_shortpixel = null;
    
    if ($is_shortpixel === null) {
        $is_shortpixel = false;
        
        // Check for direct ShortPixel actions
        if (did_action('shortpixel_before_restore_image') || 
            did_action('shortpixel_image_optimised') || 
            did_action('shortpixel_before_optimise_image')) {
            $is_shortpixel = true;
        }
        
        // Only use backtrace as last resort - it's expensive
        if (!$is_shortpixel) {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
            foreach ($backtrace as $trace) {
                if (isset($trace['class']) && strpos($trace['class'], 'ShortPixel') !== false) {
                    $is_shortpixel = true;
                    break;
                }
            }
        }
    }
    
    return $is_shortpixel;
}

/**
 * Set up ShortPixel compatibility
 * 
 * Adds necessary filters and adjustments to ensure 
 * compatibility with ShortPixel image optimizer.
 *
 * @since 2.0.0
 */
function burnaway_images_shortpixel_compatibility() {
    // Only run if ShortPixel is active
    if (!function_exists('shortpixel_init') && !class_exists('ShortPixel')) {
        return;
    }
    
    // Attach all filters at once with common callback
    $make_processable = function($value, $size) {
        return $size === 'full' ? true : $value;
    };
    
    add_filter('shortpixel_is_processable_size', $make_processable, 10, 2);
    add_filter('shortpixel_skip_processable_check', $make_processable, 10, 2);
    
    // Single filter for all image sizes
    add_filter('shortpixel_image_sizes', function($sizes) {
        return array_unique(array_merge($sizes, array('full')));
    });
    
    // Optimized file handling
    add_filter('shortpixel_get_attached_file', function($file, $id) {
        // Cache expensive operations
        static $scaled_replacements = array();
        
        if (strpos($file, '-scaled.') !== false) {
            if (!isset($scaled_replacements[$file])) {
                $original_file = str_replace('-scaled.', '.', $file);
                if (file_exists($original_file)) {
                    $scaled_replacements[$file] = $original_file;
                } else {
                    $scaled_replacements[$file] = $file;
                }
            }
            return $scaled_replacements[$file];
        }
        return $file;
    }, 10, 2);
}