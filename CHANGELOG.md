# Changelog

## 2.4.1 - 2025-03-20

### Bug Fixes

- Fixed: Media replacement functionality issues on attachment edit screen
- Fixed: Circular dependency in media replacement processing functions
- Fixed: Missing return statement in ShortPixel compatibility function
- Fixed: Attachment URL handling with proper file existence validation
- Fixed: Form submission issues in media replacement interface
- Fixed: File path construction for media replacement uploads
- Fixed: Undefined variables in thumbnail deletion function
- Fixed: Inconsistent function registration in responsive images module
- Fixed: Error handling for missing files during media replacement
- Improved: Error messages for failed media replacement attempts

## 2.4.0 - 2025-03-17

### New Features

- Added: Configurable CDN URL templates in admin settings
- Added: Support for URL template tokens ({width}, {height}, {format}, {quality})
- Added: Separate templates for standard and cropped images

### Improvements

- Improved: More flexible CDN integration with custom URL formats
- Improved: Code organization with dedicated URL generator function
- Improved: Type safety throughout the codebase
- Improved: Comprehensive error handling and input validation
- Added: Robust settings sanitization for security and stability
- Enhanced: Function naming consistency and better parameter validation
- Fixed: Missing return statement in ShortPixel compatibility function
- Fixed: Potential undefined function references
- Enhanced: Debugging tools with better error handling
- Updated: Documentation with examples of URL template usage

## 2.3.3 - 2025-03-17

- Added: New functionality to replace missing media files via admin interface
- Fixed: PHP fatal errors from undefined functions in debug tools
- Fixed: Array access errors in responsive images implementation
- Fixed: Updated function name references in debug tools
- Improved: Better type checking before accessing array values
- Improved: Consistent function naming conventions throughout the plugin
- Improved: File loading order to prevent dependency issues

## 2.3.2 - 2025-03-16

- Fixed: Fatal errors due to undefined functions in debug tools and responsive images implementation
- Fixed: Corrected function name references in initialization hooks
- Fixed: Reordered file includes to ensure dependencies load in correct order
- Improved: Added proper function fallbacks to prevent fatal errors

## 2.3.0 - 2025-03-15

### New Features

- **Media Replacement**: Added ability to replace media files while maintaining the same attachment ID
- Option to append timestamp to replaced files to avoid caching issues
- Full compatibility with WP Offload Media for cloud storage
- Complete integration with ShortPixel Image Optimizer for automatic optimization of replaced images
- Built-in handling for cleaning up old thumbnails after replacement
- Simple interface integrated into WordPress media library

### Improvements

- Added media replacement option to the settings page
- Enhanced attachment edit screen with replace media button
- Added documentation for new media replacement feature
- Automatic detection and cleanup of ShortPixel backup files during media replacement

### Bug Fixes

- Add `should_apply_responsive_images()` check based on settings in WP admin.
- Moved debug-tools.php call to fire after main plugin functions.

## 2.2.1 - 2025-03-14

### Improvements

- Added fallback mechanism for missing original image files
- Plugin now gracefully handles cases where original (pre-scaled) files aren't available
- Enhanced file existence checking for more robust operation
- Added debug logging for missing original files when WP_DEBUG is enabled

### Bug Fixes

- Fixed potential 404 errors when original image files were deleted or unavailable
- Improved compatibility with sites where image management has been inconsistent

## 2.2.0 - 2025-03-13

### Major Changes

- **Plugin Renamed**: Renamed from "Burnaway Image Tweaks" to "Burnaway Images" for better clarity
- **Complete Code Refactoring**: Restructured the entire plugin into a modular architecture
- **Improved Documentation**: Completely rewrote code comments for better developer understanding

### Architecture Improvements

- Split monolithic plugin into separate functional components:
  - `core.php`: Central settings and utility functions
  - `admin.php`: Admin interface and settings management
  - `image-processing.php`: WordPress image processing modifications
  - `responsive-images.php`: Fastly responsive image implementation
  - `compatibility.php`: Third-party plugin compatibility (ShortPixel)

### Additional Improvements

- Added settings migration from old plugin name to new name
- Enhanced function documentation with detailed parameter and return value descriptions
- Improved code organization with logical function grouping
- Optimized attachment metadata handling with efficient caching
- Better theme-specific image size handling
- Added comprehensive developer notes throughout the codebase

### Bug Fixes

- Fixed potential conflicts with ShortPixel image processing
- Improved scaled image detection and correction
- More reliable original image URL generation

## Version 2.1.2 (March 12, 2025)

- Added customizable responsive sizes via admin settings
- Removed caching functions to fix page generation issues
- Improved compatibility with page builders and dynamic content
- Added input field for comma-separated responsive image widths
- Updated responsive image generation to use custom widths
- Fixed potential stale data issues when editing images

## Version 2.1.1 (March 12, 2025)

- Added support for lazy loading images (loading="lazy")
- Added support for async image decoding (decoding="async")
- Added settings to toggle lazy loading and async decoding
- Updated settings UI with new options
- Added settings descriptions for the new features
- Updated How It Works section with new features

## Version 2.1 (March 11, 2025)

- Significant performance optimizations across all plugin functions
- Added caching for attachment metadata to reduce database queries
- Added static caching for theme sizes and responsive breakpoints
- Optimized ShortPixel detection to avoid expensive backtrace operations
- Improved file existence checking with caching
- Added centralized theme size definition functions
- More efficient responsive image handling with early returns
- Combined content image filtering into single regex pass
- Optimized image URL retrieval with result caching
- Added conditional loading of plugin features
- Fixed issue with w768 image size handling
- Implemented more efficient ShortPixel compatibility filters
- Enhanced responsive image srcset generation for better performance
- Fixed theme-specific width handling (192, 340, 540, 768, 1000px)
- Added static array of theme sizes to avoid repeated lookups

## Version 2.0.1 (March 11, 2025)

- Added support for theme-specific image sizes (192, 340, 540, 768, 1000px)
- Fixed ShortPixel compatibility issues
- Enhanced original image handling to ensure Fastly always uses original uploads
- Fixed "Not Processable: Image Size Excluded" error in ShortPixel
- Added get_original_image_url function for consistent original image references
- Updated responsive sizes to include theme-specific breakpoints
- Updated settings page with theme size information
- Fixed function name reference causing fatal error
- Improved metadata handling for better ShortPixel integration

## [2.0] - 2025-03-11

- Added support for theme-specific image sizes (192, 340, 540, 768, 1000px)
- Enhanced srcset generation to prioritize theme breakpoints
- Implemented get_original_image_url() function to ensure original images are used
- Completely disabled WordPress image scaling for all uploads
- Special handling for w192 size (192×336px with smart crop)
- Updated responsive size arrays across all functions
- Added settings page information about theme-specific sizes
- Registered custom image sizes to ensure proper size handling
- Improved image resizing via Fastly query parameters
- Fixed content image filtering to use theme-specific sizes

## [1.9] - 2025-03-08

Changed plugin name and description

### Added

- Admin UI under Media menu for configuring plugin settings
- Comprehensive settings for controlling image optimization features
- Quality and format controls for Fastly image optimization
- Original image enforcement system that prevents WordPress scaled versions
- Filter to replace -scaled image references with original images in content
- Advanced metadata modification to ensure original image dimensions are used
- Settings persistence through plugin activation/deactivation

### Changed

- Reorganized code structure for better maintainability
- Separated debug tools into a standalone file (loaded only when WP_DEBUG is true)
- Improved filter application with conditional loading based on settings
- Enhanced image URL handling for better compatibility with Fastly optimization

### Fixed

- Issue with WordPress using -scaled images instead of originals
- Attachment URL handling to properly reference original files
- Settings structure to ensure consistent defaults across installations
- Image metadata handling to reflect original dimensions

## [1.8] - 2025-03-08

### Added

- Debugging tools for frontend srcset verification
- Troubleshooting guide in documentation for responsive image issues

### Changed

- Increased filter priority to 999 to prevent conflicts with other plugins
- Improved function completion to ensure proper srcset implementation
- Enhanced documentation with troubleshooting steps

### Fixed

- Issue with srcset attributes not appearing on frontend images
- Missing return statements in image processing functions
- Filter conflicts with themes and other plugins

## [1.7] - 2025-03-08

### Added

- Special handling for w192 size with smart cropping via Fastly
- Improved error handling and validation for image metadata

### Changed

- Removed redundant image size registration for sizes already defined in theme
- Updated code to follow WordPress coding standards
- Improved URL construction with proper escaping
- Refined image quality settings for Fastly (90 quality)

### Fixed

- Potential issues with malformed URLs in srcset
- Edge cases with missing image metadata

## [1.6] - 2025-03-01

### Added

- Custom responsive image handling with Fastly integration
- AVIF and WebP format support via Fastly query parameters
- Dynamic srcset generation based on original image dimensions
- Configurable responsive breakpoints (320px to 1920px)

### Changed

- Removed WordPress default responsive image handling
- Updated image attribute filtering for Fastly compatibility
- Modified plugin description to reflect Fastly optimization

### Maintained

- Original image file integrity
- WordPress thumbnail generation prevention
- Image compression disabling (100% quality)

## [1.5] - 2025-03-01

### Added

- Complete removal of non-thumbnail size generation
- Intermediate image sizes prevention filter
- Enhanced image size removal functionality

### Changed

- Updated plugin documentation
- Improved image size removal process
- Enhanced compatibility with WordPress core

## [1.3] - 2025-02-20

### Removed

- Image resizing functionality
- Custom full image size settings

### Maintained

- Thumbnail disabling
- Compression disabling (100% quality)
- Responsive image disabling

## [1.2] - 2025-02-20

### Added

- Image resizing functionality using 'contain' approach
- Support for maintaining original aspect ratio
- Automatic resizing of large images to max dimensions (1920x1080)
- Preservation of original dimensions for smaller images

### Changed

- Modified `set_custom_full_image_size` to use actual image dimensions
- Updated image handling to use WordPress's built-in editor
- Improved quality preservation with 100% compression setting

### Fixed

- Issue with image dimensions not being updated in metadata
- Potential image distortion when resizing

## [1.1] - Previous Version

### Features

- Disabled default WordPress thumbnail sizes
- Disabled image compression
- Disabled responsive images feature
- Basic custom image size support

## [1.0] - Initial Release

### Features

- Basic thumbnail disabling
- Compression disabling
- Responsive image disabling
