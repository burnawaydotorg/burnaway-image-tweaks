<?php
/*
Plugin Name: Burnaway Image Tweaks
Description: Custom features for Burnaway. Optimize image delivery with Fastly CDN while disabling WordPress's default image processing for improved performance and quality.

Author: brandon sheats
Version: 2.0.1
*/

// Include debug tools if WP_DEBUG is enabled
if (defined('WP_DEBUG') && WP_DEBUG) {
    require_once plugin_dir_path(__FILE__) . 'debug-tools.php';
}

// Get plugin settings
function get_burnaway_image_settings() {
    $defaults = array(
        'disable_thumbnails' => true,
        'disable_compression' => true,
        'enable_responsive' => true,
        'disable_scaling' => true,
        'quality' => 90,
        'formats' => array('auto'),
    );
    
    $settings = get_option('burnaway_image_tweaks_settings', $defaults);
    return $settings;
}

// Hook to run on plugin activation
function burnaway_image_tweaks_activate() {
    // Set default options if they don't exist
    if (false === get_option('burnaway_image_tweaks_settings')) {
        add_option('burnaway_image_tweaks_settings', array(
            'disable_thumbnails' => true,
            'disable_compression' => true,
            'enable_responsive' => true,
            'disable_scaling' => true,
            'quality' => 90,
            'formats' => array('auto'),
        ));
    }
}
register_activation_hook(__FILE__, 'burnaway_image_tweaks_activate');

// Remove default image sizes completely
function disable_default_image_sizes() {
    $settings = get_burnaway_image_settings();
    
    if (!isset($settings['disable_thumbnails']) || !$settings['disable_thumbnails']) {
        return;
    }
    
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
    $settings = get_burnaway_image_settings();
    
    if (!isset($settings['disable_thumbnails']) || !$settings['disable_thumbnails']) {
        return $sizes;
    }
    
    // Keep the sizes empty for WordPress, but make exception for ShortPixel
    if (doing_filter('shortpixel_image_sizes')) {
        return array('full' => 'full'); // Keep 'full' for ShortPixel
    }
    
    return array();
}
add_filter('intermediate_image_sizes_advanced', 'disable_image_sizes');

// Disable WordPress default compression
function disable_image_compression($quality) {
    $settings = get_burnaway_image_settings();
    
    if (!isset($settings['disable_compression']) || !$settings['disable_compression']) {
        return $quality;
    }
    
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

// Improved get_original_image_url function
function get_original_image_url($attachment_id) {
    $upload_dir = wp_upload_dir();
    $metadata = wp_get_attachment_metadata($attachment_id);
    
    // First check if there's an original_image (WordPress scaled the image)
    if (isset($metadata['original_image']) && isset($metadata['file'])) {
        $file_dir = dirname($metadata['file']);
        $original_file = $file_dir === '.' ? $metadata['original_image'] : $file_dir . '/' . $metadata['original_image'];
        return $upload_dir['baseurl'] . '/' . $original_file;
    }
    
    // Otherwise use the regular file path
    if (isset($metadata['file'])) {
        return $upload_dir['baseurl'] . '/' . $metadata['file'];
    }
    
    // Fallback to standard function
    return wp_get_attachment_url($attachment_id);
}

// Custom responsive image handling with Fastly
function custom_responsive_images($attr, $attachment, $size) {
    $settings = get_burnaway_image_settings();
    
    // Skip if responsive images are disabled
    if (!isset($settings['enable_responsive']) || !$settings['enable_responsive']) {
        return $attr;
    }
    
    // Get the selected quality setting
    $quality = isset($settings['quality']) ? intval($settings['quality']) : 90;
    
    // Get the format settings
    $formats = isset($settings['formats']) && is_array($settings['formats']) ? $settings['formats'] : array('auto');
    $format = !empty($formats) ? $formats[0] : 'auto';
    
    // Return early if not an image attachment
    if (!wp_attachment_is_image($attachment->ID)) {
        return $attr;
    }
    
    // Make sure $attr is an array
    if (!is_array($attr)) {
        $attr = array();
    }

    // Check for theme-specific sizes
    $theme_sizes = array('w192', 'w340', 'w540', 'w768', 'w1000');
    if (in_array($size, $theme_sizes)) {
        // Use original image URL, not scaled version
        $src = get_original_image_url($attachment->ID);
        $width = (int)str_replace('w', '', $size);
        
        // Special case for w192 which needs exact dimensions
        if ($size === 'w192') {
            $attr['src'] = "$src?width=192&height=336&fit=crop&crop=smart&format=$format&quality=$quality";
            // Remove srcset for this specific use case to ensure exact dimensions
            if (isset($attr['srcset'])) {
                unset($attr['srcset']);
            }
        } else {
            // For other theme sizes, set the width but keep responsive srcset
            $attr['src'] = "$src?width=$width&format=$format&quality=$quality";
            
            // Get image metadata
            $image_meta = wp_get_attachment_metadata($attachment->ID);
            if ($image_meta && isset($image_meta['width'])) {
                $orig_width = intval($image_meta['width']);
                
                // Define responsive widths with theme sizes
                $sizes = array(192, 340, 480, 540, 768, 1000, 1024, 1440, 1920);
                $srcset = array();
                
                // Build srcset with appropriate sizes
                foreach ($sizes as $size_width) {
                    // Skip sizes too close to original or larger than original
                    if ($orig_width - $size_width < 50) {
                        continue;
                    }
                    
                    // Add this size to srcset
                    $srcset[] = "$src?width=$size_width&format=$format&quality=$quality {$size_width}w";
                }
                
                // Add the original image to srcset
                $srcset[] = "$src?format=$format&quality=$quality {$orig_width}w";
                
                if (!empty($srcset)) {
                    $attr['srcset'] = implode(', ', $srcset);
                    $attr['sizes'] = "(max-width: {$width}px) 100vw, {$width}px";
                }
            }
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
    // Use original image URL, not scaled version
    $src = get_original_image_url($attachment->ID);
    
    if (empty($src) || $width <= 0) {
        return $attr;
    }
    
    // Define responsive widths
    $sizes = array(192, 340, 480, 540, 768, 1000, 1024, 1440, 1920);
    $srcset = array();
    
    // Build srcset entries for each size
    foreach ($sizes as $size_width) {
        // Skip sizes that are too close to or larger than the original
        if ($width - $size_width < 50) {
            continue;
        }
        
        // Add entry with Fastly optimization parameters
        $srcset[] = "$src?width=$size_width&format=$format&quality=$quality {$size_width}w";
    }
    
    // Add the original image to the srcset
    $srcset[] = "$src?format=$format&quality=$quality {$width}w";
    
    // Only add srcset if we have entries
    if (!empty($srcset)) {
        $attr['srcset'] = implode(', ', $srcset);
        
        // Calculate sizes attribute if not already present
        if (empty($attr['sizes'])) {
            $attr['sizes'] = '(max-width: 1920px) 100vw, 1920px';
        }
    }
    
    // Set optimized src for the main image
    $attr['src'] = "$src?format=$format&quality=$quality";
    
    return $attr;
}

function apply_responsive_images_filter() {
    $settings = get_burnaway_image_settings();
    
    // Only apply if responsive images are enabled
    if (!is_admin() && isset($settings['enable_responsive']) && $settings['enable_responsive']) {
        add_filter('wp_get_attachment_image_attributes', 'custom_responsive_images', 9999, 3);
        add_filter('the_content', 'filter_content_images', 9999);
        add_filter('wp_calculate_image_srcset', 'override_image_srcset', 9999, 5);
    }
}
add_action('init', 'apply_responsive_images_filter');

function override_image_srcset($sources, $size_array, $image_src, $image_meta, $attachment_id) {
    $settings = get_burnaway_image_settings();
    
    // Skip if responsive images are disabled
    if (!isset($settings['enable_responsive']) || !$settings['enable_responsive']) {
        return $sources;
    }
    
    // Get the selected quality setting
    $quality = isset($settings['quality']) ? intval($settings['quality']) : 90;
    
    // Get the format settings
    $formats = isset($settings['formats']) && is_array($settings['formats']) ? $settings['formats'] : array('auto');
    $format = !empty($formats) ? $formats[0] : 'auto';
    
    // Replace image_src with original image
    $src = get_original_image_url($attachment_id);
    
    $width = isset($image_meta['width']) ? intval($image_meta['width']) : 0;
    if ($width <= 0) return $sources;
    
    $new_sources = array();
    $sizes = array(192, 340, 480, 540, 768, 1000, 1024, 1440, 1920);
    
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

// Filter images in content
function filter_content_images($content) {
    // Use regex to find and replace img tags
    $pattern = '/<img(.*?)src=[\'"](.*?)[\'"](.*?)>/i';
    $content = preg_replace_callback($pattern, 'replace_content_image', $content);
    
    // When processing image tags in content, ensure we're using original URLs
    // Add logic to identify and replace scaled image URLs with original ones
    $content = preg_replace_callback('/-scaled\.(jpe?g|png|gif|webp)/i', function($matches) {
        return '.' . $matches[1]; // Remove '-scaled' suffix
    }, $content);
    
    return $content;
}

// Replace individual images in content
function replace_content_image($matches) {
    $img_attrs = $matches[1] . $matches[3];
    $src = $matches[2];
    
    // Skip if already processed
    if (strpos($src, 'format=auto') !== false) {
        return $matches[0];
    }
    
    // Add Fastly parameters
    $new_src = $src . '?format=auto&quality=90';
    
    // Create srcset if not already present
    if (strpos($img_attrs, 'srcset=') === false) {
        $sizes = array(192, 340, 480, 540, 768, 1000, 1024, 1280, 1536, 1920);
        $srcset = array();
        
        foreach ($sizes as $width) {
            $srcset[] = "$src?width={$width}&format=auto&quality=90 {$width}w";
        }
        
        $srcset_attr = ' srcset="' . implode(', ', $srcset) . '"';
        $sizes_attr = ' sizes="(max-width: 1920px) 100vw, 1920px"';
        
        return '<img' . $img_attrs . ' src="' . $new_src . '"' . $srcset_attr . $sizes_attr . '>';
    }
    
    return '<img' . $img_attrs . ' src="' . $new_src . '">';
}

// Register the w192 size as a recognized size to ensure it's passed to our filter
function register_custom_sizes() {
    add_image_size('w192', 192, 336, true);
    add_image_size('w340', 340, 0, false);
    add_image_size('w540', 540, 0, false);
    add_image_size('w768', 768, 0, false);
    add_image_size('w1000', 1000, 0, false);
}
add_action('after_setup_theme', 'register_custom_sizes');

// Remove the scaling dimensions
add_filter('upload_dir', 'remove_scaling_dimensions');
function remove_scaling_dimensions($upload) {
    // Remove reference to undefined function
    return $upload;
}

// Only apply big_image_size_threshold filter if setting is enabled
function maybe_disable_image_scaling() {
    $settings = get_burnaway_image_settings();
    
    if (isset($settings['disable_scaling']) && $settings['disable_scaling']) {
        add_filter('big_image_size_threshold', '__return_false');
        
        // Add other scaling-related filters only if this setting is enabled
        add_filter('wp_get_attachment_image_src', 'use_original_images', 10, 4);
        add_filter('wp_get_attachment_url', 'fix_attachment_urls', 10, 2);
        add_filter('the_content', 'fix_content_image_urls', 9);
        add_filter('wp_get_attachment_metadata', 'fix_attachment_metadata', 10, 2);
    }
}
add_action('init', 'maybe_disable_image_scaling');

// Force WordPress to use original images, not -scaled versions
function use_original_images($image, $attachment_id, $size, $icon) {
    if (is_array($image) && isset($image[0]) && strpos($image[0], '-scaled.') !== false) {
        // Get the original image URL without the -scaled suffix
        $original_url = str_replace('-scaled.', '.', $image[0]);
        
        // Only replace if original file exists
        $original_path = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $original_url);
        if (file_exists($original_path)) {
            $image[0] = $original_url;
            
            // If dimensions are included, update them
            if (isset($image[1]) && isset($image[2])) {
                $size_data = getimagesize($original_path);
                if ($size_data) {
                    $image[1] = $size_data[0]; // width
                    $image[2] = $size_data[1]; // height
                }
            }
        }
    }
    return $image;
}
add_filter('wp_get_attachment_image_src', 'use_original_images', 10, 4);

// Fix attachment URLs to use original images instead of -scaled versions
function fix_attachment_urls($url, $attachment_id) {
    // Only process if it's a -scaled image
    if (strpos($url, '-scaled.') !== false) {
        $original_url = str_replace('-scaled.', '.', $url);
        $original_path = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $original_url);
        
        if (file_exists($original_path)) {
            return $original_url;
        }
    }
    return $url;
}
add_filter('wp_get_attachment_url', 'fix_attachment_urls', 10, 2);

// Ensure post content uses original images, not -scaled versions
function fix_content_image_urls($content) {
    return preg_replace_callback('/(src|srcset)="([^"]*-scaled\.[^"]*)"/', function($matches) {
        $original_url = str_replace('-scaled.', '.', $matches[2]);
        $original_path = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $original_url);
        
        if (file_exists($original_path)) {
            return $matches[1] . '="' . $original_url . '"';
        }
        
        return $matches[0];
    }, $content);
}
add_filter('the_content', 'fix_content_image_urls', 9); // Run before other content filters

// Modify image metadata but preserve info for ShortPixel
function fix_attachment_metadata($data, $attachment_id) {
    // Check if this is image metadata
    if (!is_array($data) || !isset($data['file'])) {
        return $data;
    }
    
    // Check if we're in a ShortPixel context and need original metadata
    $is_shortpixel = false;
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
    foreach ($backtrace as $trace) {
        if (isset($trace['class']) && strpos($trace['class'], 'ShortPixel') !== false) {
            $is_shortpixel = true;
            break;
        }
    }
    
    if ($is_shortpixel) {
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
add_filter('wp_get_attachment_metadata', 'fix_attachment_metadata', 10, 2);

// Add admin menu under Media
function burnaway_image_tweaks_menu() {
    add_submenu_page(
        'upload.php',                     // Parent slug (Media menu)
        'BA Image Tweaks Settings',     // Page title
        'BA Image Optimization',              // Menu title
        'manage_options',                  // Capability
        'burnaway-image-tweaks',           // Menu slug
        'burnaway_image_tweaks_settings_page' // Function
    );
}
add_action('admin_menu', 'burnaway_image_tweaks_menu');

// Register settings
function burnaway_image_tweaks_register_settings() {
    register_setting('burnaway_image_tweaks_options', 'burnaway_image_tweaks_settings');
    
    // Default settings
    $default_settings = array(
        'disable_thumbnails' => true,
        'disable_compression' => true,
        'enable_responsive' => true,
        'disable_scaling' => true,
        'quality' => 90,
        'formats' => array('auto'),
    );
    
    // If settings don't exist, create them
    if (false === get_option('burnaway_image_tweaks_settings')) {
        add_option('burnaway_image_tweaks_settings', $default_settings);
    }
}
add_action('admin_init', 'burnaway_image_tweaks_register_settings');

// Settings page content
function burnaway_image_tweaks_settings_page() {
    // Get current settings
    $settings = get_option('burnaway_image_tweaks_settings');
    ?>
    <div class="wrap">
        <h1>Burnaway Image Tweaks Settings</h1>
        
        <form method="post" action="options.php">
            <?php settings_fields('burnaway_image_tweaks_options'); ?>
            
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Image Processing</th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><span>Image Processing</span></legend>
                            
                            <label for="disable_thumbnails">
                                <input name="burnaway_image_tweaks_settings[disable_thumbnails]" type="checkbox" id="disable_thumbnails" value="1" <?php checked('1', isset($settings['disable_thumbnails']) ? $settings['disable_thumbnails'] : false); ?>>
                                Disable WordPress default image sizes
                            </label><br>
                            
                            <label for="disable_compression">
                                <input name="burnaway_image_tweaks_settings[disable_compression]" type="checkbox" id="disable_compression" value="1" <?php checked('1', isset($settings['disable_compression']) ? $settings['disable_compression'] : false); ?>>
                                Disable WordPress image compression (use original quality)
                            </label><br>
                            
                            <label for="enable_responsive">
                                <input name="burnaway_image_tweaks_settings[enable_responsive]" type="checkbox" id="enable_responsive" value="1" <?php checked('1', isset($settings['enable_responsive']) ? $settings['enable_responsive'] : false); ?>>
                                Enable Fastly-powered responsive images
                            </label><br>
                            
                            <label for="disable_scaling">
                                <input name="burnaway_image_tweaks_settings[disable_scaling]" type="checkbox" id="disable_scaling" value="1" <?php checked('1', isset($settings['disable_scaling']) ? $settings['disable_scaling'] : false); ?>>
                                Disable WordPress image scaling (use original images)
                            </label><br>
                            
                            <p class="description">Control how WordPress processes and serves images.</p>
                        </fieldset>
                    </td>
                </tr>
                
                <tr valign="top">
                    <th scope="row"><label for="image_quality">Image Quality</label></th>
                    <td>
                        <input name="burnaway_image_tweaks_settings[quality]" type="number" min="1" max="100" id="image_quality" value="<?php echo esc_attr(isset($settings['quality']) ? $settings['quality'] : 90); ?>" class="small-text">
                        <p class="description">Quality parameter for Fastly image optimization (1-100).</p>
                    </td>
                </tr>
                
                <tr valign="top">
                    <th scope="row">Image Format</th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><span>Image Format</span></legend>
                            
                            <label for="format_auto">
                                <input name="burnaway_image_tweaks_settings[formats][]" type="checkbox" id="format_auto" value="auto" <?php checked(true, isset($settings['formats']) && in_array('auto', $settings['formats'])); ?>>
                                Auto (recommended - let Fastly choose optimal format)
                            </label><br>
                            
                            <label for="format_webp">
                                <input name="burnaway_image_tweaks_settings[formats][]" type="checkbox" id="format_webp" value="webp" <?php checked(true, isset($settings['formats']) && in_array('webp', $settings['formats'])); ?>>
                                WebP
                            </label><br>
                            
                            <label for="format_avif">
                                <input name="burnaway_image_tweaks_settings[formats][]" type="checkbox" id="format_avif" value="avif" <?php checked(true, isset($settings['formats']) && in_array('avif', $settings['formats'])); ?>>
                                AVIF
                            </label><br>
                            
                            <p class="description">Specify image formats for delivery. Auto is recommended.</p>
                        </fieldset>
                    </td>
                </tr>
                
                <tr valign="top">
                    <th scope="row">Responsive Sizes</th>
                    <td>
                        <p>Currently using these widths for responsive images: 192px, 340px, 480px, 540px, 768px, 1000px, 1024px, 1440px, 1920px.</p>
                        <p>Theme-specific widths: 192px, 340px, 540px, 768px, 1000px are respected when directly requested.</p>
                        <p class="description">To customize these sizes, you'll need to modify the code directly in the plugin.</p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(); ?>
        </form>
        
        <div class="card" style="max-width: 800px; margin-top: 20px;">
            <h2>How It Works</h2>
            <p>This plugin optimizes your WordPress images by:</p>
            <ol>
                <li>Preventing WordPress from generating multiple thumbnail sizes</li>
                <li>Disabling WordPress's default image compression</li>
                <li>Using Fastly's image optimization for responsive images</li>
                <li>Ensuring original uploaded images are used instead of scaled versions</li>
            </ol>
            <p><strong>Note:</strong> This plugin works best when your site is behind Fastly or a similar CDN that supports image optimization parameters.</p>
        </div>
    </div>
    <?php
}

// Disable WordPress big image scaling
function disable_big_image_scaling() {
    $settings = get_burnaway_image_settings(); // FIXED: Correct function name
    
    if (!isset($settings['disable_scaling']) || !$settings['disable_scaling']) {
        return;
    }
    
    // Set big image threshold to a very large number, effectively disabling scaling
    add_filter('big_image_size_threshold', '__return_false');
    // For WordPress versions that don't respect the false value:
    add_filter('big_image_size_threshold', function() { return 99999; });
}
add_action('init', 'disable_big_image_scaling');

// Enhanced ShortPixel compatibility
function shortpixel_compatibility() {
    // For ShortPixel, ensure the original image is accessible
    add_filter('shortpixel_get_attached_file', function($file, $id) {
        // Check if this is a scaled image
        if (strpos($file, '-scaled.') !== false) {
            $original_file = str_replace('-scaled.', '.', $file);
            if (file_exists($original_file)) {
                return $original_file; // Return original file path
            }
        }
        return $file;
    }, 10, 2);
    
    // Make sure 'full' size is always included
    add_filter('shortpixel_image_sizes', function($sizes) {
        if (!in_array('full', $sizes)) {
            $sizes[] = 'full';
        }
        return $sizes;
    });
    
    // Always make original images processable
    add_filter('shortpixel_is_processable_size', function($processable, $size) {
        if ($size === 'full') {
            return true;
        }
        return $processable;
    }, 10, 2);
    
    // Ensure ShortPixel can process the image
    add_filter('shortpixel_skip_processable_check', function($skip, $size_key) {
        if ($size_key === 'full') {
            return true;
        }
        return $skip;
    }, 10, 2);
    
    // Help ShortPixel find the actual original file
    add_filter('shortpixel_actual_file_paths', function($paths, $id) {
        // Add path to original file if it exists
        $attachment_meta = wp_get_attachment_metadata($id);
        
        if (isset($attachment_meta['_shortpixel_original'])) {
            $upload_dir = wp_upload_dir();
            $orig_file = $attachment_meta['_shortpixel_original']['original_image'];
            $file_dir = dirname($attachment_meta['_shortpixel_original']['file']);
            $original_path = $upload_dir['basedir'] . '/' . $file_dir . '/' . $orig_file;
            
            if (file_exists($original_path)) {
                $paths[] = $original_path;
            }
        }
        
        return $paths;
    }, 10, 2);
}
add_action('plugins_loaded', 'shortpixel_compatibility');
?>