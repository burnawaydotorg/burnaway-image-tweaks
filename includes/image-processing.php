<?php
/**
 * Image Processing Functionality
 *
 * Handles core image processing including disabling thumbnails,
 * preventing compression, and disabling image scaling.
 *
 * @package BurnawayImages
 * @since 2.2.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize image processing functionality
 *
 * @since 2.2.0
 */
function burnaway_images_processing_init() {
    // Main disabling functions
    add_action('init', 'burnaway_images_disable_default_sizes');
    add_filter('intermediate_image_sizes_advanced', 'burnaway_images_disable_sizes');
    add_filter('jpeg_quality', 'burnaway_images_disable_compression');
    add_filter('wp_editor_set_quality', 'burnaway_images_disable_compression');
    add_action('init', 'burnaway_images_maybe_disable_scaling');
    add_action('init', 'burnaway_images_disable_big_scaling');
    
    // Register custom sizes if needed
    add_action('after_setup_theme', 'burnaway_images_register_custom_sizes');
}

/**
 * Remove default WordPress image sizes
 * 
 * Completely removes WordPress default image sizes to prevent them from being generated.
 *
 * @since 1.0.0
 */
function burnaway_images_disable_default_sizes() {
    $settings = burnaway_images_get_settings();
    
    if (!isset($settings['disable_thumbnails']) || !$settings['disable_thumbnails']) {
        return;
    }
    
    // Remove all default sizes
    remove_image_size('thumbnail');
    remove_image_size('medium');
    remove_image_size('medium_large');
    remove_image_size('large');
    remove_image_size('1536x1536');
    remove_image_size('2048x2048');
}

/**
 * Prevent WordPress from generating additional sizes
 * 
 * Filters intermediate_image_sizes_advanced to return an empty array,
 * preventing WordPress from generating any additional sizes.
 * 
 * @since 1.0.0
 * @param array $sizes Array of sizes to be generated
 * @return array Empty array or 'full' size for ShortPixel
 */
function burnaway_images_disable_sizes($sizes) {
    $settings = burnaway_images_get_settings();
    
    if (!isset($settings['disable_thumbnails']) || !$settings['disable_thumbnails']) {
        return $sizes;
    }
    
    // Make exception for ShortPixel
    if (doing_filter('shortpixel_image_sizes')) {
        return array('full' => 'full');
    }
    
    return array();
}

/**
 * Disable WordPress default compression
 * 
 * Sets JPEG quality to 100 to prevent WordPress from compressing images.
 *
 * @since 1.0.0
 * @param int $quality JPEG quality level
 * @return int 100 for no compression or original quality
 */
function burnaway_images_disable_compression($quality) {
    $settings = burnaway_images_get_settings();
    
    if (!isset($settings['disable_compression']) || !$settings['disable_compression']) {
        return $quality;
    }
    
    return 100;
}

/**
 * Register theme-specific custom image sizes
 * 
 * Registers custom sizes needed by the theme. These don't generate files
 * but register the size names for the theme to request.
 *
 * @since 2.0.0
 */
function burnaway_images_register_custom_sizes() {
    $settings = burnaway_images_get_settings();
    
    // Only register if responsive images are enabled
    if (!isset($settings['enable_responsive']) || !$settings['enable_responsive']) {
        return;
    }
    
    // Register theme-specific sizes
    add_image_size('w192', 192, 336, true);  // Special size with smart crop
    add_image_size('w340', 340, 0, false);
    add_image_size('w540', 540, 0, false);
    add_image_size('w768', 768, 0, false);
    add_image_size('w1000', 1000, 0, false);
}

/**
 * Remove the scaling dimensions
 *
 * @param array $upload Upload directory data
 * @return array Unmodified upload directory data
 */
function burnaway_images_remove_scaling_dimensions($upload) {
    return $upload;
}
add_filter('upload_dir', 'burnaway_images_remove_scaling_dimensions');

/**
 * Conditionally disable WordPress image scaling
 * 
 * Applies appropriate filters to disable WordPress image scaling
 * based on plugin settings.
 *
 * @since 1.3.0
 */
function burnaway_images_maybe_disable_scaling() {
    $settings = burnaway_images_get_settings();
    
    if (isset($settings['disable_scaling']) && $settings['disable_scaling']) {
        add_filter('big_image_size_threshold', '__return_false');
        
        // Add other scaling-related filters
        add_filter('wp_get_attachment_image_src', 'burnaway_images_use_original_images', 10, 4);
        add_filter('wp_get_attachment_url', 'burnaway_images_fix_attachment_urls', 10, 2);
        add_filter('the_content', 'burnaway_images_fix_content_image_urls', 9);
        add_filter('wp_get_attachment_metadata', 'burnaway_images_fix_attachment_metadata', 10, 2);
    }
}

/**
 * Disable WordPress big image scaling
 *
 * @since 2.0.0
 */
function burnaway_images_disable_big_scaling() {
    $settings = burnaway_images_get_settings();
    
    if (!isset($settings['disable_scaling']) || !$settings['disable_scaling']) {
        return;
    }
    
    // Set big image threshold to a very large number, effectively disabling scaling
    add_filter('big_image_size_threshold', '__return_false');
    // For WordPress versions that don't respect the false value:
    add_filter('big_image_size_threshold', function() { return 99999; });
}

/**
 * Use original images instead of scaled versions when possible
 * 
 * Forces WordPress to use original images instead of -scaled versions when available.
 * Falls back to scaled version if original doesn't exist.
 *
 * @since 2.2.1
 * @param array $image Image data array
 * @param int $attachment_id Attachment ID
 * @param string|array $size Requested size
 * @param bool $icon Whether to treat as icon
 * @return array Modified image data
 */
function burnaway_images_use_original_images($image, $attachment_id, $size, $icon) {
    if (is_array($image) && isset($image[0]) && strpos($image[0], '-scaled.') !== false) {
        // Get original image URL without -scaled suffix
        $original_url = str_replace('-scaled.', '.', $image[0]);
        
        // Check if original file exists
        $original_path = str_replace(
            burnaway_images_get_upload_dir()['baseurl'], 
            burnaway_images_get_upload_dir()['basedir'], 
            $original_url
        );
        
        if (burnaway_images_file_exists($original_path)) {
            $image[0] = $original_url;
            
            // Update dimensions if available
            if (isset($image[1]) && isset($image[2])) {
                $size_data = getimagesize($original_path);
                if ($size_data) {
                    $image[1] = $size_data[0]; // width
                    $image[2] = $size_data[1]; // height
                }
            }
        } else if (defined('WP_DEBUG') && WP_DEBUG) {
            // Log that we're sticking with scaled version
            error_log('Burnaway Images: Original image not found, using scaled version: ' . $original_path);
        }
    }
    return $image;
}

/**
 * Fix attachment URLs to use original images when available
 * 
 * Replaces -scaled URLs with original URLs if original exists.
 *
 * @since 2.2.1
 * @param string $url Attachment URL
 * @param int $attachment_id Attachment ID
 * @return string Modified URL
 */
function burnaway_images_fix_attachment_urls($url, $attachment_id) {
    if (strpos($url, '-scaled.') !== false) {
        $original_url = str_replace('-scaled.', '.', $url);
        $original_path = str_replace(
            burnaway_images_get_upload_dir()['baseurl'], 
            burnaway_images_get_upload_dir()['basedir'], 
            $original_url
        );
        
        if (burnaway_images_file_exists($original_path)) {
            return $original_url;
        } else if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Burnaway Images: Original image not found, using scaled version: ' . $original_path);
        }
    }
    return $url;
}

/**
 * Fix image URLs in content when originals are available
 * 
 * Replaces -scaled URLs with original URLs in post content when original files exist.
 *
 * @since 2.2.1
 * @param string $content Post content
 * @return string Modified content
 */
function burnaway_images_fix_content_image_urls($content) {
    return preg_replace_callback('/(src|srcset)="([^"]*-scaled\.[^"]*)"/', function($matches) {
        $original_url = str_replace('-scaled.', '.', $matches[2]);
        $original_path = str_replace(
            burnaway_images_get_upload_dir()['baseurl'], 
            burnaway_images_get_upload_dir()['basedir'], 
            $original_url
        );
        
        if (file_exists($original_path)) {
            return $matches[1] . '="' . $original_url . '"';
        }
        
        // If original doesn't exist, keep using the scaled version
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Burnaway Images: Original image not found in content, using scaled: ' . $original_path);
        }
        
        return $matches[0];
    }, $content);
}

/**
 * Fix attachment metadata to use original image dimensions
 * 
 * Modifies attachment metadata to replace -scaled dimensions with original dimensions.
 *
 * @since 2.0.0
 * @param array $data Attachment metadata
 * @param int $attachment_id Attachment ID
 * @return array Modified metadata
 */
function burnaway_images_fix_attachment_metadata($data, $attachment_id) {
    // Check if this is image metadata
    if (!is_array($data) || !isset($data['file'])) {
        return $data;
    }
    
    // Check if we're in a ShortPixel context
    if (burnaway_images_is_shortpixel_request()) {
        // Don't modify metadata for ShortPixel operations
        return $data;
    }
    
    // If this is a scaled image, get original dimensions
    if (isset($data['original_image'])) {
        // Keep a backup of the original metadata for ShortPixel
        $data['_shortpixel_original'] = array(
            'file' => $data['file'],
            'original_image' => $data['original_image']
        );
        
        $original_file = dirname($data['file']) . '/' . $data['original_image'];
        $upload_dir = wp_upload_dir();
        $original_path = $upload_dir['basedir'] . '/' . $original_file;
        
        if (file_exists($original_path)) {
            $size_data = getimagesize($original_path);
            if ($size_data) {
                $data['width'] = $size_data[0];
                $data['height'] = $size_data[1];
                $data['file'] = $original_file;
                // Keep original_image for ShortPixel but make it usable for our system
                $data['_original_file'] = $data['original_image'];
                unset($data['original_image']);
            }
        }
    }
    
    return $data;
}