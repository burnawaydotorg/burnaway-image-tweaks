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
    // Check if user can upload files - MODIFIED to use edit_posts instead of upload_files
    if (!current_user_can('edit_posts')) {
        wp_die(__('You do not have permission to upload files.', 'burnaway-images'));
    }
    
    // Get the ID
    $post_id = intval($_GET['post_id']);
    
    // Check if user can edit this post
    if (!current_user_can('edit_post', $post_id)) {
        wp_die(__('You do not have permission to edit this file.', 'burnaway-images'));
    }
    
    // Check if media replace is enabled in settings
    $settings = burnaway_images_get_settings();
    
    // Only add hooks if enabled (default to enabled if setting doesn't exist)
    if (!isset($settings['enable_media_replace']) || $settings['enable_media_replace']) {
        add_action('attachment_submitbox_misc_actions', 'burnaway_images_add_replace_button', 10);
        add_action('admin_post_burnaway_replace_media', 'burnaway_images_process_replacement');
        add_action('attachment_submitbox_misc_actions', 'burnaway_images_missing_media_ui', 20);
        add_action('admin_init', 'burnaway_images_process_missing_media_replacement');
    }
}
add_action('admin_init', 'burnaway_images_media_replace_init');

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
        'edit_posts', // Changed from 'upload_files' to 'edit_posts'
        'burnaway-replace-media',
        'burnaway_images_replace_media_page'
    );
}
add_action('admin_menu', 'burnaway_images_add_submenu_replace_media');

/**
 * Display the media replacement page
 *
 * @since 2.3.0
 */
function burnaway_images_replace_media_page() {
    if (!current_user_can('edit_posts')) { // Changed from 'upload_files' to 'edit_posts'
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
        
        <form enctype="multipart/form-data" method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <?php wp_nonce_field('burnaway_replace_media', 'burnaway_replace_media_nonce'); ?>
            <input type="hidden" name="action" value="burnaway_replace_media">
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
        burnaway_images_disable_shortpixel_filters($attachment_id);
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
 * Process media replacement form submission
 *
 * @since 2.3.0
 */
function burnaway_images_process_replacement() {
    // Verify nonce
    if (!isset($_POST['burnaway_replace_media_nonce']) || 
        !wp_verify_nonce($_POST['burnaway_replace_media_nonce'], 'burnaway_replace_media')) {
        wp_die(__('Security check failed', 'burnaway-images'), __('Error', 'burnaway-images'), array('response' => 403));
    }
    
    // Check user permissions - Changed from 'upload_files' to 'edit_posts'
    if (!current_user_can('edit_posts')) {
        wp_die(__('You do not have permission to upload files', 'burnaway-images'), __('Error', 'burnaway-images'), array('response' => 403));
    }
    
    // Validate attachment ID
    if (!isset($_POST['attachment_id'])) {
        wp_die(__('No attachment specified', 'burnaway-images'), __('Error', 'burnaway-images'));
    }
    
    $attachment_id = intval($_POST['attachment_id']);
    if ($attachment_id <= 0) {
        wp_die(__('Invalid attachment ID', 'burnaway-images'), __('Error', 'burnaway-images'));
    }
    
    // Check if file was uploaded
    if (!isset($_FILES['burnaway_replacement_file']) || $_FILES['burnaway_replacement_file']['error'] !== UPLOAD_ERR_OK) {
        $error_message = isset($_FILES['burnaway_replacement_file']) ? 
            wp_get_upload_error_string($_FILES['burnaway_replacement_file']['error']) : 
            __('No file was uploaded', 'burnaway-images');
        wp_die($error_message, __('Upload Error', 'burnaway-images'));
    }
    
    // Get replacement type (timestamp or direct)
    $replace_type = isset($_POST['burnaway_replace_type']) ? sanitize_text_field($_POST['burnaway_replace_type']) : 'timestamp';
    
    // Handle the media replacement
    burnaway_images_handle_media_replace();
    
    // Redirect to media edit screen on success
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
 * @param int $attachment_id Attachment ID
 */
function burnaway_images_disable_shortpixel_filters($attachment_id = null) {
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
    
    // Also remove any ShortPixel metadata to force re-optimization if an ID is provided
    if ($attachment_id) {
        delete_post_meta($attachment_id, '_shortpixel_status');
        delete_post_meta($attachment_id, '_shortpixel_meta');
    }
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
    $original_filename = basename($metadata['file']); // Add this line to define the variable
    
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

/**
 * Replace missing media file with a new one
 *
 * Allows replacing media files that no longer exist on the server
 * with new versions from backups or other sources.
 *
 * @param int $attachment_id The attachment ID to replace
 * @param string $replacement_path Full path to the replacement file
 * @return bool|WP_Error True on success, WP_Error on failure
 */
function burnaway_images_replace_missing_media($attachment_id, $replacement_path) {
    // Validate input
    if (!$attachment_id || !file_exists($replacement_path)) {
        return new WP_Error('invalid_input', 'Invalid attachment ID or replacement file path.');
    }
    
    // Get attachment data
    $attachment = get_post($attachment_id);
    if (!$attachment || 'attachment' !== $attachment->post_type) {
        return new WP_Error('invalid_attachment', 'Invalid attachment ID.');
    }
    
    // Get attachment metadata
    $metadata = wp_get_attachment_metadata($attachment_id);
    $old_file = get_attached_file($attachment_id, true);
    $upload_dir = wp_upload_dir();
    
    // Check if original file exists
    $file_exists = file_exists($old_file);
    
    // Prepare paths
    $path_parts = pathinfo($old_file);
    $old_dirname = $path_parts['dirname'];
    
    // Create directory if it doesn't exist
    if (!file_exists($old_dirname)) {
        wp_mkdir_p($old_dirname);
    }
    
    // Copy new file to the correct location
    if (!@copy($replacement_path, $old_file)) {
        return new WP_Error('copy_failed', 'Failed to copy replacement file.');
    }
    
    // Update file permissions
    $stat = stat(dirname($old_file));
    $perms = $stat['mode'] & 0000666;
    @chmod($old_file, $perms);
    
    // Get new file info
    $filetype = wp_check_filetype($replacement_path);
    $new_mime = $filetype['type'];
    $file_size = filesize($replacement_path);
    
    // Update attachment metadata
    if (!$file_exists || !is_array($metadata)) {
        // Generate completely new metadata for missing files
        $metadata = wp_generate_attachment_metadata($attachment_id, $old_file);
    } else {
        // Update existing metadata
        $imagesize = getimagesize($old_file);
        if ($imagesize) {
            $metadata['width'] = $imagesize[0];
            $metadata['height'] = $imagesize[1];
            $metadata['filesize'] = $file_size;
            
            // Remove old sizes to force regeneration
            if (isset($metadata['sizes'])) {
                unset($metadata['sizes']);
            }
               
            // Regenerate thumbnails
            $metadata = wp_generate_attachment_metadata($attachment_id, $old_file);
        }
    }
    
    // Update attachment record
    wp_update_attachment_metadata($attachment_id, $metadata);
    
    if ($new_mime && $new_mime !== get_post_mime_type($attachment)) {
        // Update mime type in database
        wp_update_post(array(
            'ID' => $attachment_id,
            'post_mime_type' => $new_mime
        ));
    }
    
    // Clear any caches
    clean_attachment_cache($attachment_id);
    
    // Action hook for other plugins
    do_action('burnaway_images_after_replace_missing_media', $attachment_id, $old_file, $replacement_path);
        
    return true;
}

/**
 * Admin UI for replacing missing media
 * 
 * Adds a form to the media edit screen for replacing missing media
 */
function burnaway_images_missing_media_ui() {
    $post = get_post();
    if (!$post || 'attachment' !== $post->post_type) {
        return;
    }
    
    $file = get_attached_file($post->ID);
    if (file_exists($file)) {
        return; // File exists, use regular media replace
    }
    
    ?>
    <div class="missing-media-replace">
        <h3><?php _e('Replace Missing Media File', 'burnaway-images'); ?></h3>
        <p><?php _e('The original media file is missing. Upload a replacement file:', 'burnaway-images'); ?></p>
        
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('burnaway_replace_missing_media', 'burnaway_missing_media_nonce'); ?>
            <input type="hidden" name="attachment_id" value="<?php echo esc_attr($post->ID); ?>" />
            <input type="file" name="replacement_file" required />
            <p class="description"><?php _e('Select a replacement file from your computer', 'burnaway-images'); ?></p>
            <button type="submit" name="burnaway_replace_missing" class="button button-primary">
                <?php _e('Upload & Replace', 'burnaway-images'); ?>
            </button>
        </form>
    </div>
    <?php
}
add_action('attachment_submitbox_misc_actions', 'burnaway_images_missing_media_ui', 20);

/**
 * Process the missing media replacement form
 */
function burnaway_images_process_missing_media_replacement() {
    if (!isset($_POST['burnaway_replace_missing']) || !isset($_POST['attachment_id'])) {
        return;
    }
    
    // Verify nonce
    if (!isset($_POST['burnaway_missing_media_nonce']) || 
        !wp_verify_nonce($_POST['burnaway_missing_media_nonce'], 'burnaway_replace_missing_media')) {
        wp_die(__('Security check failed', 'burnaway-images'), __('Error', 'burnaway-images'), array('response' => 403));
    }
    
    // Check permissions - Changed from 'upload_files' to 'edit_posts'
    if (!current_user_can('edit_posts')) {
        wp_die(__('You do not have permission to upload files', 'burnaway-images'), __('Error', 'burnaway-images'), array('response' => 403));
    }
    
    // Validate attachment ID
    $attachment_id = intval($_POST['attachment_id']);
    if ($attachment_id <= 0) {
        wp_die(__('Invalid attachment ID', 'burnaway-images'), __('Error', 'burnaway-images'));
    }
    
    // Check if file was uploaded
    if (!isset($_FILES['replacement_file']) || $_FILES['replacement_file']['error'] !== 0) {
        $error_message = isset($_FILES['replacement_file']) ? 
            wp_get_upload_error_string($_FILES['replacement_file']['error']) : 
            __('No file was uploaded', 'burnaway-images');
        wp_die($error_message, __('Upload Error', 'burnaway-images'));
    }
    
    // Process the upload with error handling
    $result = burnaway_images_replace_missing_media($attachment_id, $_FILES['replacement_file']['tmp_name']);
    
    if (is_wp_error($result)) {
        wp_die($result->get_error_message(), __('Error', 'burnaway-images'));
    }
    
    // Redirect back with success message
    wp_redirect(admin_url('post.php?post=' . $attachment_id . '&action=edit&message=1'));
    exit;
}
add_action('admin_init', 'burnaway_images_process_missing_media_replacement');

/**
 * Add replace media button to attachment edit screen
 *
 * @since 2.3.0
 */
function burnaway_images_add_replace_button() {
    global $post;
    if (!$post || 'attachment' !== $post->post_type || !wp_attachment_is_image($post->ID)) {
        return;
    }
    
    echo '<div class="misc-pub-section">';
    echo '<a href="' . admin_url('post.php?page=burnaway-replace-media&attachment_id=' . $post->ID) . '" class="button">';
    echo __('Replace Media', 'burnaway-images');
    echo '</a>';
    echo '</div>';
}

/**
 * Filter the uploaded file before processing
 *
 * @param array $file Uploaded file data
 * @return array Filtered file data
 */
function burnaway_images_replace_upload_filter($file) {
    // Check if user can upload files - MODIFIED to use edit_posts
    if (!current_user_can('edit_posts')) {
        return $file;
    }
    
    // ... rest of function continues
}