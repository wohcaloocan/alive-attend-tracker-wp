
<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get filter parameters
$filter_type = isset($_GET['filter_type']) ? sanitize_text_field($_GET['filter_type']) : 'all';
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$quarter = isset($_GET['quarter']) ? intval($_GET['quarter']) : ceil(date('n') / 3);

// Set up filter args based on selected filter
$filter_args = array();

switch ($filter_type) {
    case 'month':
        $filter_args['year'] = $year;
        $filter_args['month'] = $month;
        $filter_title = date_i18n('F Y', mktime(0, 0, 0, $month, 1, $year));
        break;
    
    case 'quarter':
        $filter_args['year'] = $year;
        $filter_args['quarter'] = $quarter;
        $filter_title = sprintf(__('Q%d %d', 'youth-alive-attendance'), $quarter, $year);
        break;
    
    case 'year':
        $filter_args['year'] = $year;
        $filter_title = $year;
        break;
    
    case 'all':
    default:
        $filter_title = __('All Time', 'youth-alive-attendance');
        break;
}

// Get attendance data
$database = new YAAT_Database();
$attendance_counts = $database->get_attendance_counts($filter_args);

// Get available years for filter (last 5 years)
$years = range(date('Y') - 4, date('Y'));
?>

<div class="wrap yaat-admin-wrap">
    <h1><?php _e('Youth Alive Attendance Reports', 'youth-alive-attendance'); ?></h1>
    
    <div class="yaat-filters">
        <form method="get">
            <input type="hidden" name="page" value="youth-alive-attendance-reports" />
            
            <select name="filter_type" id="yaat-filter-type">
                <option value="all" <?php selected($filter_type, 'all'); ?>><?php _e('All Time', 'youth-alive-attendance'); ?></option>
                <option value="year" <?php selected($filter_type, 'year'); ?>><?php _e('Year', 'youth-alive-attendance'); ?></option>
                <option value="quarter" <?php selected($filter_type, 'quarter'); ?>><?php _e('Quarter', 'youth-alive-attendance'); ?></option>
                <option value="month" <?php selected($filter_type, 'month'); ?>><?php _e('Month', 'youth-alive-attendance'); ?></option>
            </select>
            
            <div id="yaat-year-filter" class="yaat-filter-option" style="<?php echo $filter_type === 'all' ? 'display:none;' : ''; ?>">
                <select name="year">
                    <?php foreach ($years as $y): ?>
                        <option value="<?php echo $y; ?>" <?php selected($year, $y); ?>><?php echo $y; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div id="yaat-quarter-filter" class="yaat-filter-option" style="<?php echo $filter_type !== 'quarter' ? 'display:none;' : ''; ?>">
                <select name="quarter">
                    <?php for ($i = 1; $i <= 4; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php selected($quarter, $i); ?>><?php echo sprintf(__('Q%d', 'youth-alive-attendance'), $i); ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div id="yaat-month-filter" class="yaat-filter-option" style="<?php echo $filter_type !== 'month' ? 'display:none;' : ''; ?>">
                <select name="month">
                    <?php for ($i = 1; $i <= 12; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php selected($month, $i); ?>>
                            <?php echo date_i18n('F', mktime(0, 0, 0, $i, 1)); ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <button type="submit" class="button"><?php _e('Generate Report', 'youth-alive-attendance'); ?></button>
        </form>
    </div>
    
    <div class="yaat-report-container">
        <div class="yaat-card">
            <h2><?php printf(__('Attendance Report: %s', 'youth-alive-attendance'), $filter_title); ?></h2>
            
            <?php if (empty($attendance_counts)): ?>
                <div class="yaat-no-data">
                    <?php _e('No attendance data found for this period.', 'youth-alive-attendance'); ?>
                </div>
            <?php else: ?>
                <table class="widefat yaat-report-table">
                    <thead>
                        <tr>
                            <th><?php _e('Rank', 'youth-alive-attendance'); ?></th>
                            <th><?php _e('Name', 'youth-alive-attendance'); ?></th>
                            <th><?php _e('Attendance Count', 'youth-alive-attendance'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $rank = 1;
                        foreach ($attendance_counts as $record): 
                            $user = get_userdata($record->user_id);
                            if (!$user) continue;
                        ?>
                            <tr>
                                <td><?php echo $rank++; ?></td>
                                <td><?php echo esc_html($user->display_name); ?></td>
                                <td><?php echo intval($record->count); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="yaat-export-link">
                    <a href="<?php echo admin_url('admin.php?page=youth-alive-attendance-export&mode=report&filter_type=' . $filter_type . '&year=' . $year . '&month=' . $month . '&quarter=' . $quarter); ?>" class="button">
                        <?php _e('Export This Report', 'youth-alive-attendance'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
