
/**
 * Youth Alive Attendance Tracker Admin Scripts
 */

jQuery(document).ready(function($) {
    
    // Filter type change handler
    $('#yaat-filter-type').on('change', function() {
        var filterType = $(this).val();
        
        // Hide all filter options
        $('.yaat-filter-option').hide();
        
        // Show relevant filter options based on selection
        if (filterType === 'year' || filterType === 'quarter' || filterType === 'month') {
            $('#yaat-year-filter').show();
            
            if (filterType === 'quarter') {
                $('#yaat-quarter-filter').show();
            } else if (filterType === 'month') {
                $('#yaat-month-filter').show();
            }
        }
    });
    
    // Delete attendance functionality
    var currentAttendanceId = null;
    
    $('.yaat-attendance-grid').on('click', '.yaat-delete-attendance', function(e) {
        e.stopPropagation();
        
        // Get the attendance ID
        currentAttendanceId = $(this).closest('.yaat-present').data('attendance-id');
        
        // Show confirmation modal
        $('#yaat-delete-confirm').fadeIn(200);
    });
    
    // Cancel delete
    $('#yaat-delete-confirm-no').on('click', function() {
        $('#yaat-delete-confirm').fadeOut(200);
    });
    
    // Confirm delete
    $('#yaat-delete-confirm-yes').on('click', function() {
        if (!currentAttendanceId) return;
        
        $.ajax({
            url: yaat_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'yaat_delete_attendance',
                nonce: yaat_admin.nonce,
                attendance_id: currentAttendanceId
            },
            beforeSend: function() {
                // Disable buttons during request
                $('#yaat-delete-confirm-yes, #yaat-delete-confirm-no').prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    // Hide the modal
                    $('#yaat-delete-confirm').fadeOut(200);
                    
                    // Remove the attendance mark from the UI
                    $('[data-attendance-id="' + currentAttendanceId + '"]').fadeOut(300, function() {
                        $(this).remove();
                    });
                    
                    // Show success notice
                    $('h1').after('<div class="notice notice-success is-dismissible"><p>' + response.data.message + '</p></div>');
                } else {
                    // Show error message
                    alert(response.data.message || 'Error deleting attendance record.');
                }
            },
            error: function() {
                alert('Server error. Please try again.');
            },
            complete: function() {
                // Re-enable buttons
                $('#yaat-delete-confirm-yes, #yaat-delete-confirm-no').prop('disabled', false);
                currentAttendanceId = null;
            }
        });
    });
    
    // Click outside modal to close
    $(window).on('click', function(e) {
        if ($(e.target).is('#yaat-delete-confirm')) {
            $('#yaat-delete-confirm').fadeOut(200);
        }
    });
});
