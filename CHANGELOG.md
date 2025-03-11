# Changelog for Burnaway Image Tweaks

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
