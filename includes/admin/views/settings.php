
<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$database = new YAAT_Database();

// Handle pagination
$per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 20;
$current_page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

// Get users
$users_data = $database->get_all_users(array(
    'per_page' => $per_page,
    'page' => $current_page,
    'search' => $search
));

$users = $users_data['users'];
$total_users = $users_data['total'];
$total_pages = ceil($total_users / $per_page);

?>

<div class="wrap yaat-admin-wrap">
    <h1><?php _e('Youth Alive Attendance Settings', 'youth-alive-attendance'); ?></h1>
    
    <div class="yaat-settings-container">
        <div class="yaat-settings-section">
            <h2 class="yaat-settings-title"><?php _e('User Tracking Settings', 'youth-alive-attendance'); ?></h2>
            <p><?php _e('Select which users should be tracked for attendance. Only tracked users will appear in the attendance dashboard and reports.', 'youth-alive-attendance'); ?></p>
            
            <!-- Search form -->
            <form method="get" class="yaat-search-form">
                <input type="hidden" name="page" value="youth-alive-attendance-settings" />
                <p>
                    <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php _e('Search users...', 'youth-alive-attendance'); ?>">
                    <input type="submit" class="button" value="<?php _e('Search Users', 'youth-alive-attendance'); ?>">
                </p>
            </form>
            
            <!-- Users table -->
            <table class="yaat-user-settings-table widefat">
                <thead>
                    <tr>
                        <th><?php _e('Name', 'youth-alive-attendance'); ?></th>
                        <th><?php _e('Email', 'youth-alive-attendance'); ?></th>
                        <th><?php _e('Role', 'youth-alive-attendance'); ?></th>
                        <th><?php _e('Track Attendance', 'youth-alive-attendance'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="4"><?php _e('No users found.', 'youth-alive-attendance'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo esc_html($user['name']); ?></td>
                                <td><?php echo esc_html($user['email']); ?></td>
                                <td><?php echo esc_html(ucfirst($user['role'])); ?></td>
                                <td>
                                    <label class="yaat-toggle-switch">
                                        <input type="checkbox" 
                                               class="yaat-track-user-checkbox" 
                                               data-user-id="<?php echo esc_attr($user['id']); ?>"
                                               <?php checked($user['track'], true); ?>>
                                        <span class="yaat-toggle-slider"></span>
                                    </label>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="yaat-pagination">
                    <div class="yaat-pagination-info">
                        <?php 
                        $showing_from = (($current_page - 1) * $per_page) + 1;
                        $showing_to = min($showing_from + $per_page - 1, $total_users);
                        
                        echo sprintf(
                            __('Showing %1$d to %2$d of %3$d users', 'youth-alive-attendance'),
                            $showing_from,
                            $showing_to,
                            $total_users
                        );
                        ?>
                    </div>
                    <div class="yaat-per-page">
                        <form method="get">
                            <input type="hidden" name="page" value="youth-alive-attendance-settings" />
                            <?php if (!empty($search)): ?>
                                <input type="hidden" name="s" value="<?php echo esc_attr($search); ?>" />
                            <?php endif; ?>
                            
                            <label for="per-page"><?php _e('Show:', 'youth-alive-attendance'); ?></label>
                            <select name="per_page" id="per-page" onchange="this.form.submit()">
                                <?php foreach ([10, 20, 30, 50, 100] as $option): ?>
                                    <option value="<?php echo $option; ?>" <?php selected($per_page, $option); ?>>
                                        <?php echo $option; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Handle user tracking checkbox changes
    $('.yaat-track-user-checkbox').on('change', function() {
        var userId = $(this).data('user-id');
        var isTracked = $(this).prop('checked');
        
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'yaat_update_user_tracking',
                nonce: yaat_admin.nonce,
                user_id: userId,
                track: isTracked ? 1 : 0
            },
            success: function(response) {
                if (response.success) {
                    // Optionally show a success message
                } else {
                    alert(response.data.message);
                    // Revert the checkbox if there was an error
                    $(this).prop('checked', !isTracked);
                }
            },
            error: function() {
                alert('<?php _e('An error occurred. Please try again.', 'youth-alive-attendance'); ?>');
                // Revert the checkbox on error
                $(this).prop('checked', !isTracked);
            }
        });
    });
});
</script>
