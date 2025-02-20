# Changelog for Disable Thumbnails, Compression, and Responsive Images Plugin

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
