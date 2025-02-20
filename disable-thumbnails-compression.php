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

// Set custom 'full' image size.
// You can change the values of $custom_width and $custom_height to your desired dimensions. 
// Comment out this function if you want to use the default 'full' image size.
function set_custom_full_image_size($sizes) {
    $custom_width = 1920; // Set your custom width here
    $custom_height = 1080; // Set your custom height here
    $sizes['full'] = array($custom_width, $custom_height);
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