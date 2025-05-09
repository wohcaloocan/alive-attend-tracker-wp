
<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Check if we should process an export
if (isset($_GET['mode']) && current_user_can('manage_options')) {
    $mode = sanitize_text_field($_GET['mode']);
    
    if ($mode === 'report' || $mode === 'raw') {
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
                $period = date_i18n('F_Y', mktime(0, 0, 0, $month, 1, $year));
                break;
            
            case 'quarter':
                $filter_args['year'] = $year;
                $filter_args['quarter'] = $quarter;
                $period = sprintf('Q%d_%d', $quarter, $year);
                break;
            
            case 'year':
                $filter_args['year'] = $year;
                $period = $year;
                break;
            
            case 'all':
            default:
                $period = 'All_Time';
                break;
        }
        
        // Get data
        $database = new YAAT_Database();
        
        if ($mode === 'report') {
            $data = $database->get_attendance_counts($filter_args);
            $filename = "youth_alive_attendance_report_{$period}.csv";
            
            // Generate CSV headers
            $headers = array(
                __('Rank', 'youth-alive-attendance'),
                __('Name', 'youth-alive-attendance'),
                __('User ID', 'youth-alive-attendance'),
                __('Attendance Count', 'youth-alive-attendance')
            );
            
            // Generate CSV rows
            $rows = array();
            $rank = 1;
            foreach ($data as $record) {
                $user = get_userdata($record->user_id);
                if (!$user) continue;
                
                $rows[] = array(
                    $rank++,
                    $user->display_name,
                    $user->ID,
                    $record->count
                );
            }
        } else { // raw mode
            $records = $database->get_attendance_records($filter_args);
            $filename = "youth_alive_attendance_raw_{$period}.csv";
            
            // Generate CSV headers
            $headers = array(
                __('ID', 'youth-alive-attendance'),
                __('User ID', 'youth-alive-attendance'),
                __('Name', 'youth-alive-attendance'),
                __('Email', 'youth-alive-attendance'),
                __('Attendance Date', 'youth-alive-attendance'),
                __('Created At', 'youth-alive-attendance')
            );
            
            // Generate CSV rows
            $rows = array();
            foreach ($records as $record) {
                $user = get_userdata($record->user_id);
                if (!$user) continue;
                
                $rows[] = array(
                    $record->id,
                    $user->ID,
                    $user->display_name,
                    $user->user_email,
                    $record->attendance_date,
                    $record->created_at
                );
            }
        }
        
        // Output CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM
        fputs($output, chr(239) . chr(187) . chr(191));
        
        // Write headers
        fputcsv($output, $headers);
        
        // Write rows
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
}
?>

<div class="wrap yaat-admin-wrap">
    <h1><?php _e('Youth Alive Attendance Export', 'youth-alive-attendance'); ?></h1>
    
    <div class="yaat-export-container">
        <div class="yaat-card">
            <h2><?php _e('Export Attendance Data', 'youth-alive-attendance'); ?></h2>
            
            <p><?php _e('Select the type of export and the time period you want to export.', 'youth-alive-attendance'); ?></p>
            
            <form method="get" action="">
                <input type="hidden" name="page" value="youth-alive-attendance-export" />
                
                <div class="yaat-form-group">
                    <label for="mode"><?php _e('Export Type', 'youth-alive-attendance'); ?></label>
                    <select name="mode" id="mode">
                        <option value="report"><?php _e('Report (Summary)', 'youth-alive-attendance'); ?></option>
                        <option value="raw"><?php _e('Raw Data', 'youth-alive-attendance'); ?></option>
                    </select>
                </div>
                
                <div class="yaat-form-group">
                    <label for="filter_type"><?php _e('Time Period', 'youth-alive-attendance'); ?></label>
                    <select name="filter_type" id="yaat-filter-type">
                        <option value="all"><?php _e('All Time', 'youth-alive-attendance'); ?></option>
                        <option value="year"><?php _e('Year', 'youth-alive-attendance'); ?></option>
                        <option value="quarter"><?php _e('Quarter', 'youth-alive-attendance'); ?></option>
                        <option value="month"><?php _e('Month', 'youth-alive-attendance'); ?></option>
                    </select>
                </div>
                
                <div id="yaat-year-filter" class="yaat-form-group yaat-filter-option" style="display: none;">
                    <label for="year"><?php _e('Year', 'youth-alive-attendance'); ?></label>
                    <select name="year" id="year">
                        <?php for ($i = date('Y'); $i >= date('Y') - 4; $i--): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div id="yaat-quarter-filter" class="yaat-form-group yaat-filter-option" style="display: none;">
                    <label for="quarter"><?php _e('Quarter', 'youth-alive-attendance'); ?></label>
                    <select name="quarter" id="quarter">
                        <?php for ($i = 1; $i <= 4; $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo sprintf(__('Q%d', 'youth-alive-attendance'), $i); ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div id="yaat-month-filter" class="yaat-form-group yaat-filter-option" style="display: none;">
                    <label for="month"><?php _e('Month', 'youth-alive-attendance'); ?></label>
                    <select name="month" id="month">
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo date_i18n('F', mktime(0, 0, 0, $i, 1)); ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <button type="submit" class="button button-primary">
                    <?php _e('Download CSV', 'youth-alive-attendance'); ?>
                </button>
            </form>
        </div>
    </div>
</div>
