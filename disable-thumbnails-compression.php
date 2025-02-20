<?php
/*
Plugin Name: Disable Thumbnails, Compression, and Responsive Images
Description: Disables the default WordPress thumbnail sizes, disables compression of uploaded images, allows setting a custom size for the 'full' image size, and disables WordPress responsive images.
Author: b sheats
Version: 1.2
*/

// Disable default thumbnail sizes
function disable_default_image_sizes($sizes) {
    unset($sizes['thumbnail']);
    unset($sizes['medium']);
    unset($sizes['large']);
    unset($sizes['medium_large']);
    unset($sizes['1536x1536']);
    unset($sizes['2048x2048']);
    return $sizes;
}
add_filter('intermediate_image_sizes_advanced', 'disable_default_image_sizes');

// Disable image compression
function disable_image_compression($quality) {
    return 100;
}
add_filter('jpeg_quality', 'disable_image_compression');
add_filter('wp_editor_set_quality', 'disable_image_compression');

// Add this after the existing disable_image_compression function

function resize_uploaded_image($image_data) {
    // Only process if there's no error
    if ($image_data['error'] !== 0) {
        return $image_data;
    }

    $file = $image_data['file'];
    $type = $image_data['type'];
    
    // Only process jpeg/png images
    if (!in_array($type, array('image/jpeg', 'image/png'))) {
        return $image_data;
    }

    // Get image size
    $image_size = getimagesize($file);
    if (!$image_size) {
        return $image_data;
    }

    $orig_width = $image_size[0];
    $orig_height = $image_size[1];
    $max_width = 1920;
    $max_height = 1080;

    // Calculate new dimensions using 'contain' approach
    $ratio = min($max_width / $orig_width, $max_height / $orig_height);
    
    // Only resize if the image is larger than the max dimensions
    if ($ratio < 1) {
        $new_width = round($orig_width * $ratio);
        $new_height = round($orig_height * $ratio);

        // Resize the image
        $image = wp_get_image_editor($file);
        if (!is_wp_error($image)) {
            $image->resize($new_width, $new_height, true);
            $image->save($file);
            
            // Update image metadata
            $image_data['width'] = $new_width;
            $image_data['height'] = $new_height;
        }
    }

    return $image_data;
}
add_filter('wp_handle_upload', 'resize_uploaded_image');

// Set custom 'full' image size.
// You can change the values of $custom_width and $custom_height to your desired dimensions. 
// Comment out this function if you want to use the default 'full' image size.
function set_custom_full_image_size($sizes) {
    $image_path = $_REQUEST['file'] ?? null;
    if ($image_path && file_exists($image_path)) {
        $image_size = getimagesize($image_path);
        if ($image_size) {
            // Use actual image dimensions
            $sizes['full'] = array($image_size[0], $image_size[1]);
            return $sizes;
        }
    }
    
    // Fallback to max dimensions
    $sizes['full'] = array(1920, 1080);
    return $sizes;
}
add_filter('wp_calculate_image_sizes', 'set_custom_full_image_size', 10, 1);

// Disable responsive images
function disable_responsive_images() {
    return 1;
}
add_filter('wp_calculate_image_srcset_meta', '__return_null');
add_filter('wp_calculate_image_srcset', '__return_false');
add_filter('wp_calculate_image_sizes', '__return_false');
add_filter('wp_make_content_images_responsive', '__return_false');

?>