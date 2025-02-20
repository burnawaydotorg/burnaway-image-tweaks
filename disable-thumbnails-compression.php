<?php
/*
Plugin Name: Disable Thumbnails, Compression, and Responsive Images
Description: Disables the default WordPress thumbnail sizes, disables compression of uploaded images, and disables WordPress responsive images.
Author: b sheats
Version: 1.3
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

// Disable responsive images
function disable_responsive_images() {
    return 1;
}
add_filter('wp_calculate_image_srcset_meta', '__return_null');
add_filter('wp_calculate_image_srcset', '__return_false');
add_filter('wp_calculate_image_sizes', '__return_false');
add_filter('wp_make_content_images_responsive', '__return_false');

?>