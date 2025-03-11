=== Burnaway Image Tweaks ===
Contributors: sheatsb
Tags: images, thumbnails, compression, responsive images, fastly, optimization
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 2.0
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Disables WordPress default image sizes while enabling Fastly-optimized responsive images.

== Description ==

This plugin improves WordPress image handling by:

- Disabling all default WordPress image sizes (thumbnails, medium, large, etc.)
- Preventing WordPress from generating additional sizes during upload
- Disabling WordPress default compression to preserve image quality
- Implementing Fastly-optimized responsive images using the original uploaded file
- Special handling for specific image sizes with smart cropping

**Key Features:**

- **Original Image Preservation**: Maintains the original uploaded image without generating thumbnails
- **Fastly Integration**: Uses Fastly's image optimization for responsive images
- **Smart Cropping**: Special handling for w192 size with smart cropping via Fastly
- **Format Support**: AVIF and WebP format support via Fastly query parameters
- **Dynamic Sizing**: Responsive image sizes based on viewport width
- **Quality Preservation**: 100% quality for original uploads, 90% quality for Fastly-delivered images

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/burnaway-image-tweaks` directory, or install the plugin through the WordPress plugins screen.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. No configuration needed - the plugin works automatically with your existing Fastly setup.

== Frequently Asked Questions ==

= Will this plugin affect my existing media library? =

This plugin will not modify existing media files. It only affects new uploads going forward.

= Do I need Fastly for this plugin to work? =

Yes, this plugin is designed to work with Fastly's image optimization service. Without Fastly, the responsive image functionality will not work properly.

= Can I customize the responsive image sizes? =

Currently, the responsive breakpoints are set to 320px, 480px, 768px, 1024px, 1440px, and 1920px. These can be modified by editing the plugin code.

= Does this plugin compress my original uploads? =

No, this plugin actually prevents WordPress from compressing your original uploads, preserving their quality at 100%. The Fastly-delivered versions use a 90% quality setting for optimal delivery.

= Why aren't my images showing responsive srcset attributes on the frontend? =

If srcset attributes aren't appearing:

1. **Clear your cache**: WordPress and browser caches can prevent new changes from appearing.
2. **Check plugin conflicts**: Some image optimization plugins may override this plugin's functionality.
3. **Check your theme**: Some themes bypass standard WordPress image functions.
4. **Update to latest version**: Version 1.8+ includes enhanced debugging and priority handling.
5. **Verify with browser dev tools**: Inspect your images to see if srcset attributes exist but aren't working.

For advanced troubleshooting, you can add this code to your theme's functions.php:

```php
add_action('wp_footer', 'debug_fastly_srcset');
function debug_fastly_srcset() {
    if (is_user_logged_in() && current_user_can('manage_options')) {
        $recent_img = get_posts(array('post_type' => 'attachment', 'numberposts' => 1));
        if ($recent_img) {
            echo '<!-- Fastly Srcset Debug Info: ' . esc_html(print_r(wp_get_attachment_image_attributes($recent_img[0]->ID), true)) . ' -->';
        }
    }
}
```

== Changelog ==

= 1.7 - 2025-03-08 =

- Added: Special handling for w192 size with smart cropping via Fastly
- Added: Improved error handling and validation for image metadata
- Changed: Removed redundant image size registration for sizes already defined in theme
- Changed: Updated code to follow WordPress coding standards
- Changed: Improved URL construction with proper escaping
- Changed: Refined image quality settings for Fastly (90 quality)
- Fixed: Potential issues with malformed URLs in srcset
- Fixed: Edge cases with missing image metadata

= 1.6 - 2025-03-01 =

- Added: Custom responsive image handling with Fastly integration
- Added: AVIF and WebP format support via Fastly query parameters
- Added: Dynamic srcset generation based on original image dimensions
- Added: Configurable responsive breakpoints (320px to 1920px)
- Changed: Removed WordPress default responsive image handling
- Changed: Updated image attribute filtering for Fastly compatibility
- Changed: Modified plugin description to reflect Fastly optimization
- Maintained: Original image file integrity
- Maintained: WordPress thumbnail generation prevention
- Maintained: Image compression disabling (100% quality)

= 1.5 - 2025-03-01 =

- Added: Complete removal of non-thumbnail size generation
- Added: Intermediate image sizes prevention filter
- Added: Enhanced image size removal functionality
- Changed: Updated plugin documentation
- Changed: Improved image size removal process
- Changed: Enhanced compatibility with WordPress core

= 1.3 - 2025-02-20 =

- Removed: Image resizing functionality
- Removed: Custom full image size settings
- Maintained: Thumbnail disabling
- Maintained: Compression disabling (100% quality)
- Maintained: Responsive image disabling

= 1.2 - 2025-02-20 =

- Added: Image resizing functionality using 'contain' approach
- Added: Support for maintaining original aspect ratio
- Added: Automatic resizing of large images to max dimensions (1920x1080)
- Added: Preservation of original dimensions for smaller images
- Changed: Modified `set_custom_full_image_size` to use actual image dimensions
- Changed: Updated image handling to use WordPress's built-in editor
- Changed: Improved quality preservation with 100% compression setting
- Fixed: Issue with image dimensions not being updated in metadata
- Fixed: Potential image distortion when resizing

= 1.1 - Previous Version =

- Features: Disabled default WordPress thumbnail sizes
- Features: Disabled image compression
- Features: Disabled responsive images feature
- Features: Basic custom image size support

= 1.0 - Initial Release =

- Features: Basic thumbnail disabling
- Features: Compression disabling
- Features: Responsive image disabling

== Upgrade Notice ==

= 1.7 =
This update adds special handling for w192 size images with smart cropping and improves code quality by following WordPress coding standards.

= 1.6 =
Major update with Fastly integration for responsive images, AVIF and WebP format support.

= 1.5 =
Important update that enhances image size removal functionality and WordPress compatibility.
