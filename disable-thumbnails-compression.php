<?php
/*
Plugin Name: Disable Thumbnails, Compression, and Responsive Images
Description: Disables all WordPress default image sizes, image compression, and responsive images functionality.
Author: b sheats
Version: 1.4
*/

// Remove default image sizes completely
function disable_default_image_sizes() {
    remove_image_size('medium');
    remove_image_size('medium_large');
    remove_image_size('large');
    remove_image_size('1536x1536');
    remove_image_size('2048x2048');
}
add_action('init', 'disable_default_image_sizes');

// Disable scaling of big images
add_filter('big_image_size_threshold', '__return_false');

// Disable image compression
function disable_image_compression($quality) {
    return 100;
}
add_filter('jpeg_quality', 'disable_image_compression');
add_filter('wp_editor_set_quality', 'disable_image_compression');

// Disable responsive images
function disable_responsive_images() {
    return 1;
}
add_filter('wp_calculate_image_srcset_meta', '__return_null');
add_filter('wp_calculate_image_srcset', '__return_false');
add_filter('wp_calculate_image_sizes', '__return_false');
add_filter('wp_make_content_images_responsive', '__return_false');

// Remove the scaling dimensions
add_filter('upload_dir', 'remove_scaling_dimensions');
function remove_scaling_dimensions($upload) {
    remove_filter('image_size_names_choose', 'add_image_size_names');
    return $upload;
}
?>