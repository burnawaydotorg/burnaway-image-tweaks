<?php
/*
Plugin Name: Disable Thumbnails, Compression, and Responsive Images
Description: Disables WordPress default image sizes while enabling Fastly-optimized responsive images
Author: b sheats
Version: 1.7
*/

// Remove default image sizes completely
function disable_default_image_sizes() {
    remove_image_size('thumbnail');
    remove_image_size('medium');
    remove_image_size('medium_large');
    remove_image_size('large');
    remove_image_size('1536x1536');
    remove_image_size('2048x2048');
}
add_action('init', 'disable_default_image_sizes');

// Prevent WordPress from generating additional sizes
function disable_image_sizes($sizes) {
    return array();
}
add_filter('intermediate_image_sizes_advanced', 'disable_image_sizes');

// Disable WordPress default compression
function disable_image_compression($quality) {
    return 100;
}
add_filter('jpeg_quality', 'disable_image_compression');
add_filter('wp_editor_set_quality', 'disable_image_compression');

// Custom responsive image handling with Fastly
function custom_responsive_images($attr, $attachment, $size) {
    if (!is_array($attr)) {
        $attr = array();
    }

    // Check if this is the w192 size for map-item
    if ($size === 'w192') {
        $src = wp_get_attachment_url($attachment->ID);
        $attr['src'] = "$src?width=192&height=336&fit=crop&crop=smart&format=auto&quality=90";
        // Remove srcset for this specific use case to ensure exact dimensions
        if (isset($attr['srcset'])) {
            unset($attr['srcset']);
        }
        return $attr;
    }

    // Get image metadata and original src
    $image_meta = wp_get_attachment_metadata($attachment->ID);
    if (!$image_meta) {
        return $attr;
    }

    $width = $image_meta['width'];
    $src = wp_get_attachment_url($attachment->ID);
    
    // Define responsive widths
    $sizes = array(320, 480, 768, 1024, 1440, 1920);
    $srcset = array();
    
    // Build srcset entries for each size
    foreach ($sizes as $size_width) {
        // Skip sizes that are too close to or larger than the original
        if ($width - $size_width < 50) {
            continue;
        }
        
        // Add entry with Fastly optimization parameters
        $srcset[] = "$src?width=$size_width&format=auto&quality=90 $size_width"."w";
    }
    
    // Add the original image to the srcset
    $srcset[] = "$src?format=auto&quality=90 $width"."w";
    
    // Only add srcset if we have entries
    if (!empty($srcset)) {
        $attr['srcset'] = implode(', ', $srcset);
        
        // Calculate sizes attribute if not already present
        if (empty($attr['sizes'])) {
            $attr['sizes'] = '(max-width: 1920px) 100vw, 1920px';
        }
    }
    
    // Set optimized src for the main image
    $attr['src'] = "$src?format=auto&quality=90";
    
    return $attr;
}
add_filter('wp_get_attachment_image_attributes', 'custom_responsive_images', 10, 3);

// Register the w192 size as a recognized size to ensure it's passed to our filter
function register_custom_sizes() {
    add_image_size('w192', 192, 336, true);
}
add_action('after_setup_theme', 'register_custom_sizes');

// Remove the scaling dimensions
add_filter('upload_dir', 'remove_scaling_dimensions');
function remove_scaling_dimensions($upload) {
    remove_filter('image_size_names_choose', 'add_image_size_names');
    return $upload;
}

// Disable big image size threshold to prevent auto-scaling
add_filter('big_image_size_threshold', '__return_false');
?>