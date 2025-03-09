<?php
/*
Plugin Name: Disable Thumbnails, Compression, and Responsive Images
Description: Disables WordPress default image sizes while enabling Fastly-optimized responsive images
Author: b sheats
Version: 1.8
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

// Check if we should apply responsive images
function should_apply_responsive_images() {
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

// Custom responsive image handling with Fastly
function custom_responsive_images($attr, $attachment, $size) {
    // Return early if not an image attachment
    if (!wp_attachment_is_image($attachment->ID)) {
        return $attr;
    }
    
    // Make sure $attr is an array
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

    // Check if we have width information
    if (!isset($image_meta['width']) || !is_numeric($image_meta['width'])) {
        return $attr;
    }

    $width = intval($image_meta['width']);
    $src = wp_get_attachment_url($attachment->ID);
    
    if (empty($src) || $width <= 0) {
        return $attr;
    }
    
    // Define responsive widths
    $sizes = array(320, 480, 768, 1024, 1440, 1920);
    $srcset = array();
    
    // Build srcset entries for each size
    foreach ($sizes as $size_width) {
        // Skip sizes that are too close to or larger than the original
        if ($width - $size_width < 50) {
            continue;
        }
        
        // Add entry with Fastly optimization parameters - no escaping
        $srcset[] = "$src?width=$size_width&format=auto&quality=90 {$size_width}w";
    }
    
    // Add the original image to the srcset - no escaping
    $srcset[] = "$src?format=auto&quality=90 {$width}w";
    
    // Only add srcset if we have entries
    if (!empty($srcset)) {
        $attr['srcset'] = implode(', ', $srcset);
        
        // Calculate sizes attribute if not already present
        if (empty($attr['sizes'])) {
            $attr['sizes'] = '(max-width: 1920px) 100vw, 1920px';
        }
    }
    
    // Set optimized src for the main image
    $attr['src'] = esc_url("$src?format=auto&quality=90");
    
    return $attr;
}

// Apply only on front-end
function apply_responsive_images_filter() {
    if (should_apply_responsive_images()) {
        add_filter('wp_get_attachment_image_attributes', 'custom_responsive_images', 999, 3);
        
        // Also filter post content images
        add_filter('the_content', 'filter_content_images', 999);
    }
}
add_action('wp', 'apply_responsive_images_filter');

// Filter images in content
function filter_content_images($content) {
    // Use regex to find and replace img tags
    $pattern = '/<img(.*?)src=[\'"](.*?)[\'"](.*?)>/i';
    return preg_replace_callback($pattern, 'replace_content_image', $content);
}

// Replace individual images in content
function replace_content_image($matches) {
    $img_attrs = $matches[1] . $matches[3];
    $src = $matches[2];
    
    // Skip if already processed
    if (strpos($src, 'format=auto') !== false) {
        return $matches[0];
    }
    
    // Add Fastly parameters - no escaping
    $new_src = $src . '?format=auto&quality=90';
    
    // Create srcset if not already present
    if (strpos($img_attrs, 'srcset=') === false) {
        $sizes = array(320, 480, 768, 1024, 1440, 1920);
        $srcset = array();
        
        foreach ($sizes as $width) {
            // No escaping for Fastly parameters
            $srcset[] = "$src?width={$width}&format=auto&quality=90 {$width}w";
        }
        
        $srcset_attr = ' srcset="' . esc_attr(implode(', ', $srcset)) . '"';
        $sizes_attr = ' sizes="(max-width: 1920px) 100vw, 1920px"';
        
        return '<img' . $img_attrs . ' src="' . $new_src . '"' . $srcset_attr . $sizes_attr . '>';
    }
    
    return '<img' . $img_attrs . ' src="' . $new_src . '">';
}

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

// Debug srcset (only for admins)
function debug_srcset() {
    if (is_user_logged_in() && current_user_can('manage_options')) {
        echo '<!-- Srcset debugging enabled -->';
        // Find a recent image
        $recent_img = get_posts(array('post_type' => 'attachment', 'post_mime_type' => 'image', 'numberposts' => 1));
        if ($recent_img) {
            $attr = array();
            $result = custom_responsive_images($attr, $recent_img[0], 'full');
            echo '<!-- Debug result: ' . esc_html(print_r($result, true)) . ' -->';
        }
    }
}
add_action('wp_footer', 'debug_srcset');
?>