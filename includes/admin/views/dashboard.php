
<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get current year and month
$current_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$current_month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$current_filter = isset($_GET['filter']) ? sanitize_text_field($_GET['filter']) : 'month';
$current_quarter = isset($_GET['quarter']) ? intval($_GET['quarter']) : ceil(date('n') / 3);
$per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
$current_page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;

// Get attendance data
$database = new YAAT_Database();
$attendance_data = $database->get_attendance_grid($current_year, $current_month);

// Get top attendees based on selected filter
$top_attendees = $database->get_top_attendees($current_filter, $current_year, $current_month, $current_quarter);

// Get paginated user list with attendance data
$user_attendance = $database->get_users_with_attendance(array(
    'year' => $current_year,
    'month' => $current_month,
    'per_page' => $per_page,
    'page' => $current_page
));

// Calculate pagination
$total_users = $user_attendance['total'];
$total_pages = ceil($total_users / $per_page);

// Get all tracked users for the manual attendance dropdown
$all_users = $database->get_tracked_users();

// Get available years for filter (last 5 years)
$years = range(date('Y') - 4, date('Y'));

// Get filter options
$filter_labels = array(
    'month' => __('This Month', 'youth-alive-attendance'),
    'quarter' => __('Quarter', 'youth-alive-attendance'),
    'year' => __('Year', 'youth-alive-attendance'),
    'all' => __('All Time', 'youth-alive-attendance')
);

// Format the current filter for display
$filter_display = $filter_labels[$current_filter];
if ($current_filter === 'month') {
    $filter_display = date_i18n('F Y', mktime(0, 0, 0, $current_month, 1, $current_year));
} elseif ($current_filter === 'quarter') {
    $filter_display = sprintf(__('Q%d %d', 'youth-alive-attendance'), $current_quarter, $current_year);
} elseif ($current_filter === 'year') {
    $filter_display = $current_year;
}
?>

<div class="wrap yaat-admin-wrap">
    <h1><?php _e('Youth Alive Attendance Dashboard', 'youth-alive-attendance'); ?></h1>
    
    <div class="yaat-filters">
        <form method="get">
            <input type="hidden" name="page" value="youth-alive-attendance" />
            
            <select name="year" id="yaat-year-select">
                <?php foreach ($years as $year): ?>
                    <option value="<?php echo $year; ?>" <?php selected($current_year, $year); ?>>
                        <?php echo $year; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <select name="month" id="yaat-month-select">
                <?php for ($i = 1; $i <= 12; $i++): ?>
                    <option value="<?php echo $i; ?>" <?php selected($current_month, $i); ?>>
                        <?php echo date_i18n('F', mktime(0, 0, 0, $i, 1)); ?>
                    </option>
                <?php endfor; ?>
            </select>
            
            <button type="submit" class="button"><?php _e('Filter', 'youth-alive-attendance'); ?></button>
            
            <button type="button" id="yaat-add-attendance-button" class="yaat-button">
                <span class="dashicons dashicons-plus-alt"></span>
                <?php _e('Add Attendance', 'youth-alive-attendance'); ?>
            </button>
        </form>
    </div>
    
    <!-- Dashboard Widgets -->
    <div class="yaat-dashboard-widgets">
        <!-- Top Attendees Widget -->
        <div class="yaat-widget">
            <div class="yaat-widget-header">
                <?php _e('Top Attendees', 'youth-alive-attendance'); ?>
            </div>
            <div class="yaat-widget-content">
                <form method="get" class="yaat-filter-form">
                    <input type="hidden" name="page" value="youth-alive-attendance" />
                    <input type="hidden" name="year" value="<?php echo esc_attr($current_year); ?>" />
                    <input type="hidden" name="month" value="<?php echo esc_attr($current_month); ?>" />
                    
                    <select name="filter" id="yaat-filter-select" onchange="this.form.submit()">
                        <?php foreach ($filter_labels as $value => $label): ?>
                            <option value="<?php echo $value; ?>" <?php selected($current_filter, $value); ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <?php if ($current_filter === 'quarter'): ?>
                        <select name="quarter" id="yaat-quarter-select" onchange="this.form.submit()">
                            <?php for ($i = 1; $i <= 4; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php selected($current_quarter, $i); ?>>
                                    <?php echo sprintf(__('Q%d', 'youth-alive-attendance'), $i); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    <?php endif; ?>
                </form>
                
                <div class="yaat-filter-display">
                    <?php echo sprintf(__('Showing top attendees for: %s', 'youth-alive-attendance'), $filter_display); ?>
                </div>
                
                <?php if (empty($top_attendees)): ?>
                    <div class="yaat-no-data">
                        <?php _e('No attendance data found for this period.', 'youth-alive-attendance'); ?>
                    </div>
                <?php else: ?>
                    <ul class="yaat-top-attendees-list">
                        <?php foreach ($top_attendees as $index => $attendee): ?>
                            <li class="yaat-top-attendee-item">
                                <div>
                                    <span class="yaat-top-attendee-rank"><?php echo $index + 1; ?>.</span>
                                    <span class="yaat-top-attendee-name"><?php echo esc_html($attendee['name']); ?></span>
                                </div>
                                <span class="yaat-attendance-count">
                                    <?php echo $attendee['count']; ?> <?php echo _n('day', 'days', $attendee['count'], 'youth-alive-attendance'); ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- User Attendance List Widget -->
        <div class="yaat-widget">
            <div class="yaat-widget-header">
                <?php echo sprintf(__('Attendance for %s', 'youth-alive-attendance'), date_i18n('F Y', mktime(0, 0, 0, $current_month, 1, $current_year))); ?>
            </div>
            <div class="yaat-widget-content">
                <?php if (empty($user_attendance['users'])): ?>
                    <div class="yaat-no-data">
                        <?php _e('No tracked users found. Add users from the Settings page.', 'youth-alive-attendance'); ?>
                    </div>
                <?php else: ?>
                    <table class="yaat-users-list">
                        <thead>
                            <tr>
                                <th><?php _e('Name', 'youth-alive-attendance'); ?></th>
                                <th><?php _e('Attendance', 'youth-alive-attendance'); ?></th>
                                <th><?php _e('Percentage', 'youth-alive-attendance'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($user_attendance['users'] as $user): ?>
                                <tr>
                                    <td><?php echo esc_html($user['name']); ?></td>
                                    <td>
                                        <?php 
                                        echo sprintf(
                                            __('%d / %d days', 'youth-alive-attendance'), 
                                            $user['attendance_count'], 
                                            $user['total_days']
                                        ); 
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $percentage = ($user['total_days'] > 0) ? round(($user['attendance_count'] / $user['total_days']) * 100) : 0;
                                        echo $percentage . '%'; 
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
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
                                    <input type="hidden" name="page" value="youth-alive-attendance" />
                                    <input type="hidden" name="year" value="<?php echo esc_attr($current_year); ?>" />
                                    <input type="hidden" name="month" value="<?php echo esc_attr($current_month); ?>" />
                                    <input type="hidden" name="filter" value="<?php echo esc_attr($current_filter); ?>" />
                                    
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
                    
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Monthly Attendance Grid -->
    <h2><?php _e('Monthly Attendance Grid', 'youth-alive-attendance'); ?></h2>
    <div class="yaat-attendance-grid-container">
        <table class="yaat-attendance-grid widefat">
            <thead>
                <tr>
                    <th class="yaat-name-cell"><?php _e('Name', 'youth-alive-attendance'); ?></th>
                    <?php 
                    // Generate table headers (dates)
                    $current_date = $attendance_data['start_date'];
                    while ($current_date <= $attendance_data['end_date']): 
                        $day_number = date('j', strtotime($current_date));
                        $day_name = date('D', strtotime($current_date));
                        $is_weekend = (date('N', strtotime($current_date)) >= 6);
                        ?>
                        <th class="yaat-date-cell <?php echo $is_weekend ? 'yaat-weekend' : ''; ?>">
                            <div class="yaat-date-number"><?php echo $day_number; ?></div>
                            <div class="yaat-date-day"><?php echo $day_name; ?></div>
                        </th>
                        <?php
                        $current_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
                    endwhile;
                    ?>
                    <th class="yaat-total-cell"><?php _e('Total', 'youth-alive-attendance'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($attendance_data['users'])): ?>
                    <tr>
                        <td colspan="32" class="yaat-no-data">
                            <?php _e('No attendance data found for this period.', 'youth-alive-attendance'); ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($attendance_data['users'] as $user_id => $user_data): 
                        $total_attendance = 0;
                    ?>
                        <tr>
                            <td class="yaat-name-cell"><?php echo esc_html($user_data['name']); ?></td>
                            
                            <?php 
                            // Generate attendance cells
                            $current_date = $attendance_data['start_date'];
                            while ($current_date <= $attendance_data['end_date']):
                                $is_weekend = (date('N', strtotime($current_date)) >= 6);
                                $has_attendance = !empty($user_data['dates'][$current_date]);
                                $attendance_id = $has_attendance ? $user_data['dates'][$current_date] : 0;
                                
                                if ($has_attendance) {
                                    $total_attendance++;
                                }
                            ?>
                                <td class="yaat-attendance-cell <?php echo $is_weekend ? 'yaat-weekend' : ''; ?>">
                                    <?php if ($has_attendance): ?>
                                        <div class="yaat-present" data-attendance-id="<?php echo esc_attr($attendance_id); ?>">
                                            <span class="dashicons dashicons-yes-alt"></span>
                                            <span class="yaat-delete-attendance dashicons dashicons-no-alt" title="<?php _e('Delete this attendance record', 'youth-alive-attendance'); ?>"></span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            <?php
                                $current_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
                            endwhile;
                            ?>
                            
                            <td class="yaat-total-cell">
                                <?php echo $total_attendance; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Add Attendance Modal -->
    <div id="yaat-add-attendance-modal" class="yaat-modal">
        <div class="yaat-modal-content">
            <div class="yaat-modal-header">
                <span class="yaat-modal-title"><?php _e('Add Attendance', 'youth-alive-attendance'); ?></span>
                <span class="yaat-modal-close">&times;</span>
            </div>
            
            <form id="yaat-add-attendance-form">
                <div class="yaat-form-group">
                    <label for="yaat-user"><?php _e('Select User:', 'youth-alive-attendance'); ?></label>
                    <select id="yaat-user" name="user_id" required>
                        <option value=""><?php _e('-- Select User --', 'youth-alive-attendance'); ?></option>
                        <?php foreach ($all_users as $user): ?>
                            <option value="<?php echo $user->ID; ?>"><?php echo esc_html($user->display_name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="yaat-form-group">
                    <label for="yaat-date"><?php _e('Date:', 'youth-alive-attendance'); ?></label>
                    <input type="date" id="yaat-date" name="attendance_date" required 
                           value="<?php echo date('Y-m-d'); ?>" 
                           max="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="yaat-modal-footer">
                    <button type="button" class="button yaat-cancel-button"><?php _e('Cancel', 'youth-alive-attendance'); ?></button>
                    <button type="submit" class="button button-primary yaat-submit-button"><?php _e('Add Attendance', 'youth-alive-attendance'); ?></button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="yaat-delete-confirm" class="yaat-modal">
        <div class="yaat-modal-content">
            <p><?php _e('Are you sure you want to delete this attendance record?', 'youth-alive-attendance'); ?></p>
            <button id="yaat-delete-confirm-yes" class="button button-primary"><?php _e('Yes, Delete', 'youth-alive-attendance'); ?></button>
            <button id="yaat-delete-confirm-no" class="button"><?php _e('Cancel', 'youth-alive-attendance'); ?></button>
        </div>
    </div>
</div>
