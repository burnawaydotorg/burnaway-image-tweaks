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
 *
 * @since 2.2.0
 */
function burnaway_images_responsive_init() {
    // Replace this line
    // add_filter('wp_get_attachment_image_attributes', 'custom_responsive_images', 10, 3);
    
    // With this:
    add_filter('wp_get_attachment_image_attributes', 'burnaway_images_custom_responsive_attributes', 10, 3);
    
    // Additional responsive image functionality here
}

// Make sure this function is called
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
    
    // Skip for logged-in users with contributor or higher role
    if (is_user_logged_in() && current_user_can('edit_posts')) {
        return false;
    }
    
    return true;
}

/**
 * Check if responsive images should be applied based on plugin settings
 *
 * @return bool Whether responsive images should be applied
 */
function should_apply_responsive_images() {
    $settings = get_option('burnaway_images_settings', array());
    
    // Default to true if setting doesn't exist
    $enable_responsive = isset($settings['enable_responsive']) ? (bool)$settings['enable_responsive'] : true;
    
    // Allow filtering the result
    return apply_filters('burnaway_should_apply_responsive_images', $enable_responsive);
}

/**
 * Apply responsive images filters
 * 
 * Applies all filters needed for responsive image handling
 * based on plugin settings and current context.
 *
 * @since 2.1.0
 */
function burnaway_images_apply_responsive_filters() {
    $settings = burnaway_images_get_settings();
    
    // Only apply if responsive images are enabled and should be applied
    if (isset($settings['enable_responsive']) && $settings['enable_responsive'] && burnaway_images_should_apply_responsive()) {
        add_filter('wp_get_attachment_image_attributes', 'burnaway_images_custom_responsive_attributes', 9999, 3);
        add_filter('the_content', 'burnaway_images_filter_content_images', 9999);
        add_filter('wp_calculate_image_srcset', 'burnaway_images_override_srcset', 9999, 5);
    }
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
            $attr['src'] = "{$src}?width=192&height=336&fit=crop&crop=smart&format={$format}&quality={$quality}";
            unset($attr['srcset']);
            return $attr;
        }
        
        // For other theme sizes
        $attr['src'] = "{$src}?width={$width}&format={$format}&quality={$quality}";
        
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
                    $srcset[] = "{$src}?width={$size_width}&format={$format}&quality={$quality} {$size_width}w";
                }
            }
            
            // Add original size if needed
            if ($orig_width > 0) {
                $srcset[] = "{$src}?format={$format}&quality={$quality} {$orig_width}w";
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
 * Custom filter for responsive images
 *
 * Modifies the image attributes to implement responsive image functionality
 *
 * @param array $attr       Array of image attributes
 * @param WP_Post $attachment WP_Post object for the attachment
 * @param string|array $size  Requested image size
 * @return array Modified attributes
 */
function custom_responsive_images($attr, $attachment = null, $size = 'full') {
    // Only proceed if responsive images should be applied
    if (!should_apply_responsive_images()) {
        return $attr;
    }
    
    // Get plugin settings
    $settings = get_option('burnaway_images_settings', array());
    
    // Default responsive sizes from settings or use defaults
    $responsive_sizes = isset($settings['responsive_sizes']) 
        ? array_map('trim', explode(',', $settings['responsive_sizes'])) 
        : array(192, 340, 480, 540, 768, 1000, 1024, 1440, 1920);
    
    // Only proceed if we have attachment data
    if (!$attachment) {
        return $attr;
    }
    
    // Get image URL
    $image_url = wp_get_attachment_url($attachment->ID);
    if (!$image_url) {
        return $attr;
    }
    
    // Build srcset attribute
    $srcset = array();
    foreach ($responsive_sizes as $width) {
        $width = (int)$width;
        if ($width > 0) {
            $srcset[] = "{$image_url}?w={$width} {$width}w";
        }
    }
    
    if (!empty($srcset)) {
        $attr['srcset'] = implode(', ', $srcset);
        
        // Add sizes attribute if not present
        if (!isset($attr['sizes'])) {
            $attr['sizes'] = '(max-width: 768px) 100vw, 1024px';
        }
        
        // Add loading attribute if enabled
        if (isset($settings['enable_lazy_loading']) && $settings['enable_lazy_loading']) {
            $attr['loading'] = 'lazy';
        }
        
        // Add decoding attribute if enabled
        if (isset($settings['enable_async_decoding']) && $settings['enable_async_decoding']) {
            $attr['decoding'] = 'async';
        }
    }
    
    return $attr;
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
 * Filter content images for responsive delivery
 *
 * Scans post content for <img> tags and enhances them with Fastly parameters,
 * srcset attributes, lazy loading, and async decoding for optimal delivery.
 *
 * @since 1.6.0
 * @param string $content Post content
 * @return string Modified content
 */
function burnaway_images_filter_content_images($content) {
    $settings = burnaway_images_get_settings();
    $quality = isset($settings['quality']) ? intval($settings['quality']) : 90;
    $formats = isset($settings['formats']) && is_array($settings['formats']) ? $settings['formats'] : array('auto');
    $format = !empty($formats) ? $formats[0] : 'auto';
    
    // Prepare lazy loading and async attributes
    $lazy_loading = (isset($settings['enable_lazy_loading']) && $settings['enable_lazy_loading']) ? ' loading="lazy"' : '';
    $async_decoding = (isset($settings['enable_async_decoding']) && $settings['enable_async_decoding']) ? ' decoding="async"' : '';
    
    // Single regex to handle both scaled image replacement and srcset addition
    return preg_replace_callback(
        '/<img(.*?)src=[\'"](.*?)[\'"](.*?)>/i',
        function($matches) use ($quality, $format, $lazy_loading, $async_decoding) {
            $img_attrs = $matches[1];
            $src = $matches[2];
            $after_src = $matches[3];
            
            // Skip if already has lazy loading or async attributes
            $has_lazy = (strpos($img_attrs . $after_src, 'loading=') !== false);
            $has_async = (strpos($img_attrs . $after_src, 'decoding=') !== false);
            
            // Add attributes only if they don't already exist
            $lazy_attr = (!$has_lazy) ? $lazy_loading : '';
            $async_attr = (!$has_async) ? $async_decoding : '';
            
            // Handle scaled images - do this first
            if (strpos($src, '-scaled.') !== false) {
                $original_url = str_replace('-scaled.', '.', $src);
                $original_path = str_replace(
                    burnaway_images_get_upload_dir()['baseurl'], 
                    burnaway_images_get_upload_dir()['basedir'], 
                    $original_url
                );
                
                if (file_exists($original_path)) {
                    $src = $original_url;
                }
            }
            
            // Skip if already processed
            if (strpos($src, 'format=') !== false) {
                return str_replace('>', $lazy_attr . $async_attr . '>', $matches[0]);
            }
            
            // Add Fastly parameters
            $new_src = $src . "?format={$format}&quality={$quality}";
            
            // Create srcset if not already present
            if (strpos($img_attrs . $after_src, 'srcset=') === false) {
                $sizes = burnaway_images_get_responsive_sizes();
                $srcset = array();
                
                foreach ($sizes as $width) {
                    $srcset[] = "{$src}?width={$width}&format={$format}&quality={$quality} {$width}w";
                }
                
                $srcset_attr = ' srcset="' . implode(', ', $srcset) . '"';
                $sizes_attr = ' sizes="(max-width: 1920px) 100vw, 1920px"';
                
                return "<img{$img_attrs}src=\"{$new_src}\"{$after_src}{$srcset_attr}{$sizes_attr}{$lazy_attr}{$async_attr}>";
            }
            
            return "<img{$img_attrs}src=\"{$new_src}\"{$after_src}{$lazy_attr}{$async_attr}>";
        },
        $content
    );
}

// Apply responsive filters
add_action('init', 'burnaway_images_apply_responsive_filters');