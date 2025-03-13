=== Burnaway Images ===
Contributors: sheatsb
Tags: images, thumbnails, compression, responsive images, fastly, optimization, media replacement
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 2.3.0
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Disables WordPress default image sizes while enabling Fastly-optimized responsive images and advanced media replacement.

== Description ==

This plugin improves WordPress image handling by:

- Disabling all default WordPress image sizes (thumbnails, medium, large, etc.)
- Preventing WordPress from generating additional sizes during upload
- Disabling WordPress default compression to preserve image quality
- Implementing Fastly-optimized responsive images using the original uploaded file
- Special handling for specific image sizes with smart cropping
- Replacing media files while maintaining the same attachment ID

**Key Features:**

- **Original Image Preservation**: Maintains the original uploaded image without generating thumbnails
- **Fastly Integration**: Uses Fastly's image optimization for responsive images
- **Smart Cropping**: Special handling for w192 size with smart cropping via Fastly
- **Format Support**: AVIF and WebP format support via Fastly query parameters
- **Dynamic Sizing**: Responsive image sizes based on viewport width
- **Quality Preservation**: 100% quality for original uploads, configurable quality for Fastly-delivered images
- **Media Replacement**: Replace media files while maintaining the same attachment ID
- **Cloud Storage Compatible**: Works with WP Offload Media and other cloud storage solutions
- **ShortPixel Integration**: Automatic optimization of replaced images with ShortPixel

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/burnaway-images` directory, or install the plugin through the WordPress plugins screen.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to Media > Burnaway Images to configure settings (optional - the plugin works with default settings)

== Frequently Asked Questions ==

= Will this plugin affect my existing media library? =

This plugin will not modify existing media files. It only affects new uploads going forward.

= Do I need Fastly for this plugin to work? =

Yes, this plugin is designed to work with Fastly's image optimization service. Without Fastly, the responsive image functionality will not work properly.

= Can I customize the responsive image sizes? =

Yes, you can customize the responsive breakpoints in the plugin settings page under Media > Burnaway Images.

= Does this plugin compress my original uploads? =

No, this plugin actually prevents WordPress from compressing your original uploads, preserving their quality at 100%. The Fastly-delivered versions use a configurable quality setting (default 90%) for optimal delivery.

= How does the media replacement feature work? =

The media replacement feature allows you to replace an existing image file while keeping the same attachment ID. This means all posts and pages using that image will automatically use the new version. You can access this feature from the media library or the attachment edit screen.

= Is the media replacement feature compatible with cloud storage solutions? =

Yes, the media replacement feature is compatible with WP Offload Media and other cloud storage solutions. The plugin includes special handling to ensure proper synchronization with cloud storage.

= Does the media replacement work with image optimization plugins? =

Yes, the media replacement feature has built-in compatibility with ShortPixel Image Optimizer. Replaced images are automatically queued for optimization.

= Why aren't my images showing responsive srcset attributes on the frontend? =

If srcset attributes aren't appearing:

1. **Clear your cache**: WordPress and browser caches can prevent new changes from appearing.
2. **Check plugin conflicts**: Some image optimization plugins may override this plugin's functionality.
3. **Check your theme**: Some themes bypass standard WordPress image functions.
4. **Update to latest version**: Recent versions include enhanced debugging and priority handling.
5. **Verify with browser dev tools**: Inspect your images to see if srcset attributes exist but aren't working.

For advanced troubleshooting, you can add this code to your theme's functions.php:

`
add_action('wp_footer', 'debug_fastly_srcset');
function debug_fastly_srcset() {
    if (is_user_logged_in() && current_user_can('manage_options')) {
        $recent_img = get_posts(array('post_type' => 'attachment', 'numberposts' => 1));
        if ($recent_img) {
            echo '<!-- Fastly Srcset Debug Info: ' . esc_html(print_r(wp_get_attachment_image_attributes($recent_img[0]->ID), true)) . ' -->';
        }
    }
}
`

== Changelog ==

= 2.3.0 - 2025-03-15 =

* **New Features**
* Media Replacement: Added ability to replace media files while maintaining the same attachment ID
* Option to append timestamp to replaced files to avoid caching issues
* Full compatibility with WP Offload Media for cloud storage
* Complete integration with ShortPixel Image Optimizer for automatic optimization of replaced images
* Built-in handling for cleaning up old thumbnails after replacement
* Simple interface integrated into WordPress media library

* **Improvements**
* Added media replacement option to the settings page
* Enhanced attachment edit screen with replace media button
* Added documentation for new media replacement feature
* Automatic detection and cleanup of ShortPixel backup files during media replacement

= 2.2.1 - 2025-03-14 =

* **Improvements**
* Added fallback mechanism for missing original image files
* Plugin now gracefully handles cases where original (pre-scaled) files aren't available
* Enhanced file existence checking for more robust operation
* Added debug logging for missing original files when WP_DEBUG is enabled

* **Bug Fixes**
* Fixed potential 404 errors when original image files were deleted or unavailable
* Improved compatibility with sites where image management has been inconsistent

= 2.2.0 - 2025-03-13 =

* **Major Changes**
* Plugin Renamed: Renamed from "Burnaway Image Tweaks" to "Burnaway Images" for better clarity
* Complete Code Refactoring: Restructured the entire plugin into a modular architecture
* Improved Documentation: Completely rewrote code comments for better developer understanding

* **Architecture Improvements**
* Split monolithic plugin into separate functional components:
  * core.php: Central settings and utility functions
  * admin.php: Admin interface and settings management
  * image-processing.php: WordPress image processing modifications
  * responsive-images.php: Fastly responsive image implementation
  * compatibility.php: Third-party plugin compatibility (ShortPixel)

* **Additional Improvements**
* Added settings migration from old plugin name to new name
* Enhanced function documentation with detailed parameter and return value descriptions
* Improved code organization with logical function grouping
* Optimized attachment metadata handling with efficient caching
* Better theme-specific image size handling
* Added comprehensive developer notes throughout the codebase

* **Bug Fixes**
* Fixed potential conflicts with ShortPixel image processing
* Improved scaled image detection and correction
* More reliable original image URL generation

= 2.1.2 - 2025-03-12 =

* Added customizable responsive sizes via admin settings
* Removed caching functions to fix page generation issues
* Improved compatibility with page builders and dynamic content
* Added input field for comma-separated responsive image widths
* Updated responsive image generation to use custom widths
* Fixed potential stale data issues when editing images

= 2.1.1 - 2025-03-12 =

* Added support for lazy loading images (loading="lazy")
* Added support for async image decoding (decoding="async")
* Added settings to toggle lazy loading and async decoding
* Updated settings UI with new options
* Added settings descriptions for the new features
* Updated How It Works section with new features

= 1.7 - 2025-03-08 =

* Added: Special handling for w192 size with smart cropping via Fastly
* Added: Improved error handling and validation for image metadata
* Changed: Removed redundant image size registration for sizes already defined in theme
* Changed: Updated code to follow WordPress coding standards
* Changed: Improved URL construction with proper escaping
* Changed: Refined image quality settings for Fastly (90 quality)
* Fixed: Potential issues with malformed URLs in srcset
* Fixed: Edge cases with missing image metadata

= 1.6 - 2025-03-01 =

* Added: Custom responsive image handling with Fastly integration
* Added: AVIF and WebP format support via Fastly query parameters
* Added: Dynamic srcset generation based on original image dimensions
* Added: Configurable responsive breakpoints (320px to 1920px)
* Changed: Removed WordPress default responsive image handling
* Changed: Updated image attribute filtering for Fastly compatibility
* Changed: Modified plugin description to reflect Fastly optimization
* Maintained: Original image file integrity
* Maintained: WordPress thumbnail generation prevention
* Maintained: Image compression disabling (100% quality)

== Upgrade Notice ==

= 2.3.0 =
Major update adding media replacement functionality with WP Offload Media and ShortPixel compatibility.

= 2.2.1 =
Important update that adds fallback for missing original images, preventing 404 errors.

= 2.2.0 =
Major refactoring with improved architecture and new plugin name. Adds better ShortPixel compatibility.

= 2.1.2 =
Adds customizable responsive image sizes and fixes caching issues with page builders.