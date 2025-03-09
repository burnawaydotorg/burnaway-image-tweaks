# Changelog for Disable Thumbnails, Compression, and Responsive Images Plugin

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
