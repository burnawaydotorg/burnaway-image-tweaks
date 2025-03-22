<?php
/**
 * Responsive Images Functionality
 *
 * Implements Fastly-powered responsive images using srcset and sizes attributes.
 * This module enhances image delivery by generating appropriate responsive image
 * markup for optimal performance across different devices and network conditions.
 *
 * @package BurnawayImages
 * @since 2.2.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize responsive images functionality
 *
 * Sets up the necessary action hooks only when appropriate.
 * Consolidates all responsive image initialization in one place.
 *
 * @since 2.2.0
 */
function burnaway_images_responsive_init() {
    // Get settings
    $settings = burnaway_images_get_settings();
    
    // Only apply filters if responsive images are enabled
    if (isset($settings['enable_responsive']) && $settings['enable_responsive']) {
        // First check if this should apply to the current context
        if (burnaway_images_should_apply_responsive()) {
            // Add image attribute filter
            add_filter('wp_get_attachment_image_attributes', 'burnaway_images_custom_responsive_attributes', 10, 3);
            
            // Add content filter
            add_filter('the_content', 'burnaway_images_filter_content_images', 20);
            
            // Disable core responsive images if we're handling it
            add_filter('wp_calculate_image_srcset', '__return_empty_array');
        }
    }
}

// Initialize responsive images on WordPress init
add_action('init', 'burnaway_images_responsive_init');

/**
 * Check if responsive images should be applied
 * 
 * Determines whether responsive image handling should be applied
 * based on current context and user role.
 * Excludes admin area and logged-in users with content editing capabilities.
 *
 * @since 1.6.0
 * @return bool Whether to apply responsive images
 */
function burnaway_images_should_apply_responsive() {
    // Skip in admin area
    if (is_admin()) {
        return false;
    }
    
    // Always apply on frontend (remove user role check)
    return true;
}

/**
 * Check if responsive images should be applied
 * 
 * Legacy function for backward compatibility.
 * 
 * @deprecated Use burnaway_images_should_apply_responsive() instead
 * @return bool Whether to apply responsive images
 */
function should_apply_responsive_images() {
    // Forward to the properly named function for backward compatibility
    return burnaway_images_should_apply_responsive();
}

/**
 * Generate responsive image attributes
 *
 * Adds srcset, sizes, lazy loading, async decoding, and other attributes 
 * to image markup for responsive and performant image delivery.
 *
 * @since 1.6.0
 * @param array $attr Image attributes
 * @param WP_Post $attachment Attachment post object
 * @param string|array $size Requested image size
 * @return array Modified attributes
 */
function burnaway_images_custom_responsive_attributes($attr, $attachment, $size) {
    static $theme_size_widths = array(
        'w192' => 192,
        'w340' => 340, 
        'w540' => 540,
        'w768' => 768,
        'w1000' => 1000
    );
    
    // Get settings once
    $settings = burnaway_images_get_settings();
    
    // Add lazy loading and async attributes if enabled
    if (isset($settings['enable_lazy_loading']) && $settings['enable_lazy_loading']) {
        $attr['loading'] = 'lazy';
    }
    
    if (isset($settings['enable_async_decoding']) && $settings['enable_async_decoding']) {
        $attr['decoding'] = 'async';
    }
    
    // Handle different types of size parameter
    if (is_string($size)) {
        // Check if it's a theme size
        if (!isset($theme_size_widths[$size])) {
            return $attr;
        }
        
        $width = $theme_size_widths[$size];
        $src = burnaway_images_get_original_url($attachment->ID);
        
        // Get image parameters
        $quality = isset($settings['quality']) ? intval($settings['quality']) : 90;
        $formats = isset($settings['formats']) && is_array($settings['formats']) ? $settings['formats'] : array('auto');
        $format = !empty($formats) ? $formats[0] : 'auto';
        
        // Special case for w192 - smart cropping
        if ($size === 'w192') {
            $attr['src'] = burnaway_images_get_cdn_url($src, array(
                'width' => 192,
                'height' => 336
            ), true); // Use cropped template
            unset($attr['srcset']);
            return $attr;
        }
        
        // For other theme sizes
        $attr['src'] = burnaway_images_get_cdn_url($src, array(
            'width' => $width
        ));
        
        // Build srcset efficiently
        $image_meta = burnaway_images_get_attachment_metadata($attachment->ID);
        if ($image_meta && isset($image_meta['width'])) {
            $orig_width = intval($image_meta['width']);
            $srcset = array();
            
            // Get responsive sizes once
            $responsive_sizes = burnaway_images_get_responsive_sizes();
            
            // Generate srcset entries
            foreach ($responsive_sizes as $size_width) {
                if ($orig_width - $size_width >= 50) {
                    $srcset[] = burnaway_images_get_cdn_url($src, array(
                        'width' => $size_width
                    )) . " {$size_width}w";
                }
            }
            
            // Add original size if needed
            if ($orig_width > 0) {
                $srcset[] = burnaway_images_get_cdn_url($src) . " {$orig_width}w";
            }
            
            // Set srcset if we have entries
            if (!empty($srcset)) {
                $attr['srcset'] = implode(', ', $srcset);
                $attr['sizes'] = "(max-width: {$width}px) 100vw, {$width}px";
            }
        }
    } else if (is_array($size) && !empty($size)) {
        // Handle array size (width, height)
        // Just return with lazy loading/async attributes already applied
        return $attr;
    }
    
    return $attr;
}

/**
 * Custom filter for responsive images (legacy function)
 *
 * @deprecated Use burnaway_images_custom_responsive_attributes() instead
 * @param array $attr Array of image attributes
 * @param WP_Post $attachment WP_Post object for the attachment
 * @param string|array $size Requested image size
 * @return array Modified attributes
 */
function custom_responsive_images($attr, $attachment = null, $size = 'full') {
    // Forward to the proper function for backward compatibility
    return burnaway_images_custom_responsive_attributes($attr, $attachment, $size);
}

/**
 * Override WordPress srcset calculation
 *
 * Replaces WordPress srcset with Fastly-optimized versions using
 * original images with appropriate width parameters.
 *
 * @since 1.6.0
 * @param array $sources Sources array for srcset
 * @param array $size_array Width and height of image
 * @param string $image_src URL of the image
 * @param array $image_meta Attachment metadata
 * @param int $attachment_id Attachment ID
 * @return array Modified sources array
 */
function burnaway_images_override_srcset($sources, $size_array, $image_src, $image_meta, $attachment_id) {
    $settings = burnaway_images_get_settings();
    
    // Skip if responsive images are disabled
    if (!isset($settings['enable_responsive']) || !$settings['enable_responsive']) {
        return $sources;
    }
    
    // Get image settings
    $quality = isset($settings['quality']) ? intval($settings['quality']) : 90;
    $formats = isset($settings['formats']) && is_array($settings['formats']) ? $settings['formats'] : array('auto');
    $format = !empty($formats) ? $formats[0] : 'auto';
    
    // Get original image URL
    $src = burnaway_images_get_original_url($attachment_id);
    
    // Verify we have a valid width
    $width = isset($image_meta['width']) ? intval($image_meta['width']) : 0;
    if ($width <= 0) return $sources;
    
    // Build new sources array
    $new_sources = array();
    $sizes = burnaway_images_get_responsive_sizes();
    
    // Add each responsive size
    foreach ($sizes as $size_width) {
        if ($width - $size_width < 50) continue;
        $new_sources[$size_width] = array(
            'url' => "$src?width=$size_width&format=$format&quality=$quality",
            'descriptor' => 'w',
            'value' => $size_width,
        );
    }
    
    // Add the original size
    $new_sources[$width] = array(
        'url' => "$src?format=$format&quality=$quality",
        'descriptor' => 'w',
        'value' => $width,
    );
    
    return $new_sources;
}

/**
 * Apply responsive image processing to content images
 */
function burnaway_images_filter_content_images($content) {
    // Skip if content is empty
    if (empty($content) || !is_string($content)) {
        return $content;
    }
    
    // Skip if responsive images should not be applied
    if (!burnaway_images_should_apply_responsive()) {
        return $content;
    }
    
    return preg_replace_callback(
        '/<img([^>]+)src=[\'"]((?:http[s]?:\/\/|\/\/)[^"\']+)[\'"]([^>]*)>/i',
        function($matches) {
            // Validate regex matches
            if (!isset($matches[1]) || !isset($matches[2]) || !isset($matches[3])) {
                return $matches[0]; // Return original if match structure is unexpected
            }
            
            $img_attrs = $matches[1];
            $src = $matches[2];
            $after_src = $matches[3];
            
            // Get settings with validation
            $settings = burnaway_images_get_settings();
            
            // Skip if this looks like an SVG or already has srcset
            if (strpos($src, '.svg') !== false || strpos($after_src, 'srcset') !== false) {
                return $matches[0];
            }
            
            // Get quality and format settings
            $quality = isset($settings['quality']) ? intval($settings['quality']) : 90;
            $formats = isset($settings['formats']) && is_array($settings['formats']) ? $settings['formats'] : array('auto');
            $format = !empty($formats) ? $formats[0] : 'auto';
            
            // Get responsive sizes
            $responsive_sizes = burnaway_images_get_responsive_sizes();
            
            // Build srcset
            $srcset = array();
            foreach ($responsive_sizes as $width) {
                $srcset[] = burnaway_images_get_cdn_url($src, array(
                    'width' => $width,
                    'format' => $format,
                    'quality' => $quality
                )) . " {$width}w";
            }
            
            // Only proceed if we have srcset entries
            if (empty($srcset)) {
                return $matches[0];
            }
            
            // Build sizes attribute if not present
            $sizes_attr = '';
            if (strpos($after_src, 'sizes=') === false) {
                $sizes_attr = ' sizes="(max-width: 768px) 100vw, 1024px"';
            }
            
            // Add lazy loading if enabled
            $lazy_attr = '';
            if (isset($settings['enable_lazy_loading']) && $settings['enable_lazy_loading']) {
                if (strpos($img_attrs, 'loading=') === false && strpos($after_src, 'loading=') === false) {
                    $lazy_attr = ' loading="lazy"';
                }
            }
            
            // Add async decoding if enabled
            $decode_attr = '';
            if (isset($settings['enable_async_decoding']) && $settings['enable_async_decoding']) {
                if (strpos($img_attrs, 'decoding=') === false && strpos($after_src, 'decoding=') === false) {
                    $decode_attr = ' decoding="async"';
                }
            }
            
            // Build the new image tag
            return '<img' . $img_attrs . 'src="' . $src . '"' . $after_src . 
                   ' srcset="' . implode(', ', $srcset) . '"' . 
                   $sizes_attr . $lazy_attr . $decode_attr . '>';
        },
        $content
    );
}