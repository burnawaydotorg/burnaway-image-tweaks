<?php
/**
 * Media Replacement Functionality
 *
 * Provides functionality to replace media files while maintaining the same attachment ID.
 * Adds timestamp to replaced files and maintains compatibility with WP Offload Media.
 *
 * @package BurnawayImages
 * @since 2.3.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize media replacement functionality
 *
 * @since 2.3.0
 */
function burnaway_images_media_replace_init() {
    $settings = burnaway_images_get_settings();
    
    if (isset($settings['enable_media_replace']) && $settings['enable_media_replace']) {
        // Add "Replace Media" link to media list view
        add_filter('media_row_actions', 'burnaway_images_add_media_action', 10, 2);
        
        // Add "Replace Media" link to edit attachment screen
        add_action('attachment_submitbox_misc_actions', 'burnaway_images_attachment_submitbox_link');
        
        // Admin page for replacement form
        add_action('admin_menu', 'burnaway_images_add_submenu_replace_media');
        
        // Handle form submission
        add_action('admin_init', 'burnaway_images_handle_media_replace');
    }
}
add_action('init', 'burnaway_images_media_replace_init');

/**
 * Add "Replace Media" action link in media list
 *
 * @since 2.3.0
 * @param array $actions Array of action links
 * @param object $post WP_Post object
 * @return array Modified actions array
 */
function burnaway_images_add_media_action($actions, $post) {
    if ('attachment' === $post->post_type && wp_attachment_is_image($post->ID)) {
        $url = admin_url('upload.php?page=burnaway-replace-media&attachment_id=' . $post->ID);
        $actions['replace_media'] = '<a href="' . esc_url($url) . '">' . __('Replace Media', 'burnaway-images') . '</a>';
    }
    return $actions;
}

/**
 * Add replace media link to attachment edit screen
 *
 * @since 2.3.0
 */
function burnaway_images_attachment_submitbox_link() {
    global $post;
    if (wp_attachment_is_image($post->ID)) {
        $url = admin_url('upload.php?page=burnaway-replace-media&attachment_id=' . $post->ID);
        echo '<div class="misc-pub-section">';
        echo '<a href="' . esc_url($url) . '" class="button">' . __('Replace Media', 'burnaway-images') . '</a>';
        echo '</div>';
    }
}

/**
 * Register submenu page for media replacement
 *
 * @since 2.3.0
 */
function burnaway_images_add_submenu_replace_media() {
    add_submenu_page(
        null, // No parent menu
        __('Replace Media', 'burnaway-images'),
        __('Replace Media', 'burnaway-images'),
        'upload_files',
        'burnaway-replace-media',
        'burnaway_images_replace_media_page'
    );
}

/**
 * Display the media replacement page
 *
 * @since 2.3.0
 */
function burnaway_images_replace_media_page() {
    if (!current_user_can('upload_files')) {
        wp_die(__('You do not have permission to upload files.', 'burnaway-images'));
    }

    $attachment_id = isset($_GET['attachment_id']) ? intval($_GET['attachment_id']) : 0;
    if ($attachment_id <= 0 || !wp_attachment_is_image($attachment_id)) {
        wp_die(__('Invalid attachment ID.', 'burnaway-images'));
    }
    
    $attachment = get_post($attachment_id);
    $filename = basename(get_attached_file($attachment_id));
    ?>
    <div class="wrap">
        <h1><?php _e('Replace Media', 'burnaway-images'); ?></h1>
        <p><?php printf(__('You are about to replace the file "%s".', 'burnaway-images'), esc_html($filename)); ?></p>
        
        <?php if (burnaway_images_is_shortpixel_active()) : ?>
        <div class="notice notice-info">
            <p><?php _e('ShortPixel detected! The replacement image will be automatically queued for optimization after upload.', 'burnaway-images'); ?></p>
        </div>
        <?php endif; ?>
        
        <form enctype="multipart/form-data" method="post" action="">
            <?php wp_nonce_field('burnaway_replace_media', 'burnaway_replace_media_nonce'); ?>
            <input type="hidden" name="attachment_id" value="<?php echo esc_attr($attachment_id); ?>">
            
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">
                        <label for="burnaway_replacement_file"><?php _e('New File', 'burnaway-images'); ?></label>
                    </th>
                    <td>
                        <input type="file" name="burnaway_replacement_file" id="burnaway_replacement_file" required>
                        <p class="description"><?php _e('Choose a new file to replace the current one.', 'burnaway-images'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Replacement Method', 'burnaway-images'); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><?php _e('Replacement Method', 'burnaway-images'); ?></legend>
                            <label>
                                <input type="radio" name="burnaway_replace_type" value="timestamp" checked>
                                <?php _e('Add timestamp to file name (Recommended)', 'burnaway-images'); ?>
                                <p class="description"><?php _e('Adds a timestamp to create a unique filename and avoid browser caching issues.', 'burnaway-images'); ?></p>
                            </label>
                            <br>
                            <label>
                                <input type="radio" name="burnaway_replace_type" value="direct">
                                <?php _e('Just replace the file', 'burnaway-images'); ?>
                                <p class="description"><?php _e('Keeps the exact same filename. May cause caching issues.', 'burnaway-images'); ?></p>
                            </label>
                        </fieldset>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Upload New Media', 'burnaway-images'); ?>">
            </p>
        </form>
    </div>
    <?php
}

/**
 * Handle media replacement form submission
 *
 * @since 2.3.0
 */
function burnaway_images_handle_media_replace() {
    if (!isset($_POST['burnaway_replace_media_nonce']) || !wp_verify_nonce($_POST['burnaway_replace_media_nonce'], 'burnaway_replace_media')) {
        return;
    }
    
    if (!isset($_POST['attachment_id']) || !isset($_FILES['burnaway_replacement_file']) || !current_user_can('upload_files')) {
        return;
    }
    
    $attachment_id = intval($_POST['attachment_id']);
    $replace_type = isset($_POST['burnaway_replace_type']) ? sanitize_text_field($_POST['burnaway_replace_type']) : 'timestamp';
    
    // Get the original file info
    $original_file = get_attached_file($attachment_id);
    if (!$original_file || !file_exists($original_file)) {
        wp_die(__('Original file not found.', 'burnaway-images'));
    }
    
    $original_filename = basename($original_file);
    $original_path = dirname($original_file);
    
    // Check for upload errors
    if ($_FILES['burnaway_replacement_file']['error'] !== UPLOAD_ERR_OK) {
        wp_die(__('Upload failed. Please try again.', 'burnaway-images'));
    }
    
    $uploaded_file = $_FILES['burnaway_replacement_file']['tmp_name'];
    $uploaded_filename = $_FILES['burnaway_replacement_file']['name'];
    $uploaded_type = wp_check_filetype($uploaded_filename);
    
    // Make sure the file is an image
    if (substr($uploaded_type['type'], 0, 5) !== 'image') {
        wp_die(__('The uploaded file is not an image.', 'burnaway-images'));
    }
    
    // Process the replacement
    if ($replace_type === 'timestamp') {
        // Add timestamp to filename but keep the original extension
        $pathinfo = pathinfo($original_filename);
        $base_filename = $pathinfo['filename'];
        $extension = $pathinfo['extension'];
        $timestamp = '-' . time();
        $new_filename = $base_filename . $timestamp . '.' . $extension;
        $new_file = $original_path . '/' . $new_filename;
    } else {
        // Direct replacement, using the same filename
        $new_filename = $original_filename;
        $new_file = $original_file;
    }
    
    // WP Offload Media compatibility - unhook their filters to prevent automatic offloading
    if (class_exists('Amazon_S3_And_CloudFront') || class_exists('WP_Offload_Media')) {
        burnaway_images_disable_wp_offload_media_filters();
    }
    
    // ShortPixel compatibility - unhook their filters during replacement
    if (burnaway_images_is_shortpixel_active()) {
        burnaway_images_disable_shortpixel_filters();
    }
    
    // Remove the original file before uploading the new one (only for direct replacement)
    if ($replace_type === 'direct' && file_exists($original_file)) {
        unlink($original_file);
    }
    
    // Copy the new file to the uploads directory
    if (!copy($uploaded_file, $new_file)) {
        wp_die(__('Failed to copy the new file. Please check directory permissions.', 'burnaway-images'));
    }
    
    // Delete all thumbnails and scaled versions before updating metadata
    $metadata = wp_get_attachment_metadata($attachment_id);
    burnaway_images_delete_attachment_thumbnails($attachment_id, $metadata);
    
    // Update attachment metadata
    $attachment_data = wp_generate_attachment_metadata($attachment_id, $new_file);
    wp_update_attachment_metadata($attachment_id, $attachment_data);
    
    // If using timestamp method, update the file path in the database
    if ($replace_type === 'timestamp') {
        update_attached_file($attachment_id, $new_file);
    }
    
    // WP Offload Media compatibility - manually sync the file if present
    if (class_exists('Amazon_S3_And_CloudFront') || class_exists('WP_Offload_Media')) {
        burnaway_images_sync_with_wp_offload_media($attachment_id);
    }
    
    // ShortPixel compatibility - queue image for optimization if installed
    if (burnaway_images_is_shortpixel_active()) {
        burnaway_images_trigger_shortpixel_optimization($attachment_id);
    }
    
    // Redirect back to the attachment edit screen
    wp_redirect(admin_url('post.php?post=' . $attachment_id . '&action=edit&message=1'));
    exit;
}

/**
 * Disable WP Offload Media filters during replacement
 *
 * @since 2.3.0
 */
function burnaway_images_disable_wp_offload_media_filters() {
    // For WP Offload Media 2.x (previously Amazon S3 and CloudFront)
    if (class_exists('Amazon_S3_And_CloudFront')) {
        global $as3cf;
        if ($as3cf && is_object($as3cf)) {
            remove_filter('wp_update_attachment_metadata', array($as3cf, 'wp_update_attachment_metadata'), 110);
        }
    }
    
    // For WP Offload Media 3.x
    if (class_exists('WP_Offload_Media')) {
        global $as3cf;
        if ($as3cf && is_object($as3cf)) {
            remove_filter('wp_update_attachment_metadata', array($as3cf, 'wp_update_attachment_metadata'), 110);
        }
    }
}

/**
 * Manually sync file with WP Offload Media
 *
 * @since 2.3.0
 * @param int $attachment_id Attachment ID
 */
function burnaway_images_sync_with_wp_offload_media($attachment_id) {
    // For WP Offload Media 2.x (previously Amazon S3 and CloudFront)
    if (class_exists('Amazon_S3_And_CloudFront')) {
        global $as3cf;
        if ($as3cf && is_object($as3cf) && method_exists($as3cf, 'upload_attachment_file')) {
            $as3cf->upload_attachment_file($attachment_id, get_attached_file($attachment_id), true);
        }
    }
    
    // For WP Offload Media 3.x
    if (class_exists('WP_Offload_Media')) {
        global $as3cf;
        if ($as3cf && is_object($as3cf) && method_exists($as3cf, 'get_plugin_compat')) {
            $compat = $as3cf->get_plugin_compat();
            if (method_exists($compat, 'upload_attachment')) {
                $compat->upload_attachment($attachment_id);
            }
        }
    }
}

/**
 * Check if ShortPixel plugin is active
 *
 * @since 2.3.0
 * @return bool True if ShortPixel is active
 */
function burnaway_images_is_shortpixel_active() {
    return class_exists('WPShortPixel') || class_exists('ShortPixel\ShortPixelPlugin');
}

/**
 * Disable ShortPixel filters during replacement
 *
 * @since 2.3.0
 */
function burnaway_images_disable_shortpixel_filters() {
    // For older ShortPixel versions
    if (class_exists('WPShortPixel') && isset($GLOBALS['wpShortPixel'])) {
        // Remove optimization hooks
        remove_filter('wp_generate_attachment_metadata', array($GLOBALS['wpShortPixel'], 'handleMediaLibraryImageUpload'));
        remove_filter('wp_update_attachment_metadata', array($GLOBALS['wpShortPixel'], 'handleMediaLibraryImageUpload'));
    }
    
    // For newer ShortPixel versions (2.x+)
    if (class_exists('ShortPixel\ShortPixelPlugin')) {
        // Remove through the instance if available
        $sp = \ShortPixel\ShortPixelPlugin::getInstance();
        if ($sp && method_exists($sp, 'getSpMetaDao')) {
            remove_filter('wp_generate_attachment_metadata', array($sp, 'handleMediaLibraryImageUpload'));
            remove_filter('wp_update_attachment_metadata', array($sp, 'handleMediaLibraryImageUpload'));
        }
    }
    
    // Also remove any ShortPixel metadata to force re-optimization
    delete_post_meta($attachment_id, '_shortpixel_status');
    delete_post_meta($attachment_id, '_shortpixel_meta');
}

/**
 * Trigger ShortPixel optimization for replaced image
 *
 * @since 2.3.0
 * @param int $attachment_id Attachment ID
 */
function burnaway_images_trigger_shortpixel_optimization($attachment_id) {
    // For older ShortPixel versions
    if (class_exists('WPShortPixel') && isset($GLOBALS['wpShortPixel'])) {
        if (method_exists($GLOBALS['wpShortPixel'], 'optimizeNow')) {
            $GLOBALS['wpShortPixel']->optimizeNow($attachment_id);
        }
    }
    
    // For newer ShortPixel versions (2.x+)
    if (class_exists('ShortPixel\ShortPixelPlugin')) {
        $sp = \ShortPixel\ShortPixelPlugin::getInstance();
        if ($sp && method_exists($sp, 'getSpMetaDao')) {
            $dao = $sp->getSpMetaDao();
            if (method_exists($dao, 'markForOptimization')) {
                $dao->markForOptimization($attachment_id);
            }
        }
    }
    
    // Alternative method for all versions - update a flag that ShortPixel checks
    update_post_meta($attachment_id, '_shortpixel_to_be_processed', true);
}

/**
 * Delete all thumbnails and scaled versions of an attachment
 *
 * @since 2.3.0
 * @param int $attachment_id Attachment ID
 * @param array $metadata Attachment metadata
 */
function burnaway_images_delete_attachment_thumbnails($attachment_id, $metadata) {
    if (!is_array($metadata) || !isset($metadata['file']) || !isset($metadata['sizes'])) {
        return;
    }
    
    $upload_dir = wp_upload_dir();
    $path = pathinfo($metadata['file']);
    $base_dir = $upload_dir['basedir'] . '/' . $path['dirname'];
    
    // Delete each thumbnail size
    foreach ($metadata['sizes'] as $size => $sizeinfo) {
        $file = $base_dir . '/' . $sizeinfo['file'];
        if (file_exists($file)) {
            @unlink($file);
        }
    }
    
    // Delete scaled image if it exists
    if (isset($metadata['original_image'])) {
        $scaled_file = $base_dir . '/' . str_replace($metadata['original_image'], basename($metadata['file']), '');
        if (file_exists($scaled_file)) {
            @unlink($scaled_file);
        }
    }
    
    // Also delete any ShortPixel backup files if they exist
    $backup_folder = trailingslashit($base_dir) . 'ShortpixelBackups';
    if (file_exists($backup_folder) && is_dir($backup_folder)) {
        $backup_file = trailingslashit($backup_folder) . $original_filename;
        if (file_exists($backup_file)) {
            @unlink($backup_file);
        }
        
        // Also check for backup thumbnails
        if (is_array($metadata) && isset($metadata['sizes'])) {
            foreach ($metadata['sizes'] as $size => $sizeinfo) {
                $backup_thumb = trailingslashit($backup_folder) . $sizeinfo['file'];
                if (file_exists($backup_thumb)) {
                    @unlink($backup_thumb);
                }
            }
        }
    }
}