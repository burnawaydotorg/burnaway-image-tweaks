<?php
/**
 * Debug tools for Burnaway Images
 *
 * This file contains debugging functions that are conditionally loaded.
 * These tools should only run when explicitly needed and have proper
 * conditional checks to prevent interference with normal operations.
 *
 * @package BurnawayImages
 * @since 2.4.1
 */

// Only proceed if we're in debug mode
if (!defined('WP_DEBUG') || !WP_DEBUG) {
    return;
}

/**
 * Test if filters are properly registered
 * 
 * Adds a small HTML comment to the footer for debugging filter registration
 */
function burnaway_images_test_filter_registration() {
    // Only run this in specific debugging scenarios
    if (!defined('BURNAWAY_IMAGES_DEBUG_FILTERS') || !BURNAWAY_IMAGES_DEBUG_FILTERS) {
        return;
    }
    
    add_action('wp_footer', function() {
        global $wp_filter;
        echo '<!-- Filters registered: ' . 
             (has_filter('wp_get_attachment_image_attributes', 'burnaway_images_custom_responsive_attributes') ? 'YES' : 'NO') . 
             ' -->';
        echo '<!-- Content filter registered: ' . 
             (has_filter('the_content', 'burnaway_images_filter_content_images') ? 'YES' : 'NO') . 
             ' -->';
    });
}
add_action('init', 'burnaway_images_test_filter_registration');

/**
 * Debug srcset generation
 * 
 * Outputs debug information to browser console for easier reading
 */
function burnaway_images_debug_srcset() {
    // Only run this in specific debugging scenarios
    if (!defined('BURNAWAY_IMAGES_DEBUG_SRCSET') || !BURNAWAY_IMAGES_DEBUG_SRCSET) {
        return;
    }
    
    // Only run on frontend
    if (is_admin()) {
        return;
    }
    
    add_action('wp_footer', function() {
        // Output to console for easier reading
        echo '<script>console.log("Image Optimization Debugging Started");</script>';
        
        // Check if responsive images should be applied
        $should_apply = function_exists('burnaway_images_should_apply_responsive') ? 
            burnaway_images_should_apply_responsive() : 
            'Function not defined!';
            
        echo '<script>console.log("Should apply responsive images: ' . ($should_apply === true ? 'Yes' : 'No') . '");</script>';
        
        // Find a recent image with error checking
        $recent_img = get_posts(array('post_type' => 'attachment', 'post_mime_type' => 'image', 'numberposts' => 1));
        if (!empty($recent_img) && isset($recent_img[0]) && is_object($recent_img[0])) {
            $img_id = $recent_img[0]->ID;
            $img_url = wp_get_attachment_url($img_id);
            echo '<script>console.log("Test image ID: ' . $img_id . '");</script>';
            echo '<script>console.log("Test image URL: ' . $img_url . '");</script>';
            
            // Test our filter directly
            if (function_exists('burnaway_images_custom_responsive_attributes')) {
                $attr = array();
                $result = burnaway_images_custom_responsive_attributes($attr, $recent_img[0], 'full');
                echo '<script>console.log("Filter result:", ' . json_encode($result) . ');</script>';
            } else {
                echo '<script>console.log("Filter function not defined!");</script>';
            }
        } else {
            echo '<script>console.log("No test images found!");</script>';
        }
    });
}
add_action('init', 'burnaway_images_debug_srcset');

/**
 * Detailed filter callback inspection
 * 
 * Shows all callbacks registered for the image attributes filter
 */
function burnaway_images_inspect_filter_callbacks() {
    // Only run this in specific debugging scenarios
    if (!defined('BURNAWAY_IMAGES_DEBUG_CALLBACKS') || !BURNAWAY_IMAGES_DEBUG_CALLBACKS) {
        return;
    }
    
    add_action('wp_footer', function() {
        global $wp_filter;
        if (isset($wp_filter['wp_get_attachment_image_attributes'])) {
            $callbacks = array();
            foreach ($wp_filter['wp_get_attachment_image_attributes']->callbacks as $priority => $hooks) {
                foreach ($hooks as $name => $info) {
                    $callback_name = 'Unknown';
                    if (is_string($info['function'])) {
                        $callback_name = $info['function'];
                    } elseif (is_array($info['function']) && count($info['function']) == 2) {
                        if (is_object($info['function'][0])) {
                            $callback_name = get_class($info['function'][0]) . '->' . $info['function'][1];
                        } else {
                            $callback_name = $info['function'][0] . '::' . $info['function'][1];
                        }
                    }
                    $callbacks[] = "Priority $priority: $callback_name";
                }
            }
            echo '<script>console.log("Image attribute callbacks: ", ' . json_encode($callbacks) . ');</script>';
        }
    });
}
add_action('init', 'burnaway_images_inspect_filter_callbacks');

/**
 * Test with a specific image ID
 * 
 * Uses a specific image ID from your media library for testing
 * This function is disabled by default - uncomment to use
 */
function burnaway_images_debug_with_specific_image() {
    // Only run this in specific debugging scenarios
    if (!defined('BURNAWAY_IMAGES_DEBUG_SPECIFIC') || !BURNAWAY_IMAGES_DEBUG_SPECIFIC) {
        return;
    }
    
    // This requires a specific image ID to be set
    if (!defined('BURNAWAY_IMAGES_TEST_ID')) {
        return;
    }
    
    $img_id = BURNAWAY_IMAGES_TEST_ID; // Use a constant instead of hardcoded ID
    
    add_action('wp_footer', function() use ($img_id) {
        $img_url = wp_get_attachment_url($img_id);
        echo '<script>console.log("Specific test image URL: ' . $img_url . '");</script>';
        
        // Get the image object
        $img_post = get_post($img_id);
        if ($img_post) {
            // Test our filter directly
            $attr = array();
            $result = burnaway_images_custom_responsive_attributes($attr, $img_post, 'full');
            echo '<script>console.log("Direct filter result:", ' . json_encode($result) . ');</script>';
            
            // Output the actual image with our filter
            $html = wp_get_attachment_image($img_id, 'full');
            echo '<div style="border:2px solid red; padding:10px; margin:20px 0;">
                <p>Test image output:</p>
                ' . $html . '
                <pre style="background:#eee; padding:10px; overflow:auto;">' . htmlspecialchars($html) . '</pre>
            </div>';
        } else {
            echo '<script>console.log("Image ID ' . $img_id . ' not found!");</script>';
        }
    });
}
add_action('init', 'burnaway_images_debug_with_specific_image');

/**
 * Instructions for enabling debug features
 * 
 * Add to wp-config.php to enable specific debug features:
 * 
 * // Enable general debugging
 * define('WP_DEBUG', true);
 * 
 * // Enable specific debug modules
 * define('BURNAWAY_IMAGES_DEBUG_FILTERS', true);    // Debug filter registration
 * define('BURNAWAY_IMAGES_DEBUG_SRCSET', true);     // Debug srcset generation
 * define('BURNAWAY_IMAGES_DEBUG_CALLBACKS', true);  // Debug filter callbacks
 * 
 * // Test with a specific image (enable this and set an ID)
 * define('BURNAWAY_IMAGES_DEBUG_SPECIFIC', true);
 * define('BURNAWAY_IMAGES_TEST_ID', 123);           // Replace with actual image ID
 */