
<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get current year and month
$current_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$current_month = isset($_GET['month']) ? intval($_GET['month']) : date('n');

// Get attendance data
$database = new YAAT_Database();
$attendance_data = $database->get_attendance_grid($current_year, $current_month);

// Get available years for filter (last 5 years)
$years = range(date('Y') - 4, date('Y'));
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
        </form>
    </div>
    
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
    
    <div id="yaat-delete-confirm" class="yaat-modal">
        <div class="yaat-modal-content">
            <p><?php _e('Are you sure you want to delete this attendance record?', 'youth-alive-attendance'); ?></p>
            <button id="yaat-delete-confirm-yes" class="button button-primary"><?php _e('Yes, Delete', 'youth-alive-attendance'); ?></button>
            <button id="yaat-delete-confirm-no" class="button"><?php _e('Cancel', 'youth-alive-attendance'); ?></button>
        </div>
    </div>
</div>
