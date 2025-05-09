
// Youth Alive Attendance Tracker Admin Scripts

jQuery(document).ready(function($) {
    // Variables for attendance deletion
    var currentAttendanceId = 0;
    var deleteModal = $('#yaat-delete-confirm');
    
    // Show delete confirmation modal when clicking delete icon
    $(document).on('click', '.yaat-delete-attendance', function(e) {
        e.stopPropagation();
        currentAttendanceId = $(this).parent().data('attendance-id');
        deleteModal.show();
    });
    
    // Handle delete confirmation
    $('#yaat-delete-confirm-yes').on('click', function() {
        if (currentAttendanceId > 0) {
            $.ajax({
                url: yaat_admin.ajax_url,
                method: 'POST',
                data: {
                    action: 'yaat_delete_attendance',
                    nonce: yaat_admin.nonce,
                    attendance_id: currentAttendanceId
                },
                success: function(response) {
                    if (response.success) {
                        // Refresh the page to show updated data
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                    deleteModal.hide();
                },
                error: function() {
                    alert('An error occurred while deleting the attendance record.');
                    deleteModal.hide();
                }
            });
        }
    });
    
    // Cancel delete
    $('#yaat-delete-confirm-no').on('click', function() {
        deleteModal.hide();
        currentAttendanceId = 0;
    });
    
    // Close modal when clicking outside
    $(window).on('click', function(e) {
        if ($(e.target).hasClass('yaat-modal')) {
            $('.yaat-modal').hide();
            currentAttendanceId = 0;
        }
    });
    
    // Add Attendance Modal
    var addAttendanceModal = $('#yaat-add-attendance-modal');
    var addAttendanceForm = $('#yaat-add-attendance-form');
    
    // Show add attendance modal
    $('#yaat-add-attendance-button').on('click', function() {
        addAttendanceModal.show();
    });
    
    // Close add attendance modal
    $('.yaat-modal-close, .yaat-cancel-button').on('click', function() {
        addAttendanceModal.hide();
    });
    
    // Handle add attendance form submission
    addAttendanceForm.on('submit', function(e) {
        e.preventDefault();
        
        var userId = $('#yaat-user').val();
        var attendanceDate = $('#yaat-date').val();
        
        if (!userId || !attendanceDate) {
            alert('Please fill in all required fields.');
            return;
        }
        
        $.ajax({
            url: yaat_admin.ajax_url,
            method: 'POST',
            data: {
                action: 'yaat_add_manual_attendance',
                nonce: yaat_admin.nonce,
                user_id: userId,
                attendance_date: attendanceDate
            },
            beforeSend: function() {
                $('.yaat-submit-button').prop('disabled', true).text('Adding...');
            },
            success: function(response) {
                $('.yaat-submit-button').prop('disabled', false).text('Add Attendance');
                
                if (response.success) {
                    addAttendanceModal.hide();
                    addAttendanceForm[0].reset();
                    alert(yaat_admin.add_attendance_success);
                    location.reload(); // Refresh to show new data
                } else {
                    alert(response.data.message || yaat_admin.add_attendance_error);
                }
            },
            error: function() {
                $('.yaat-submit-button').prop('disabled', false).text('Add Attendance');
                alert('An error occurred. Please try again.');
            }
        });
    });
    
    // Initialize datepicker for the date input
    if ($.datepicker) {
        $('#yaat-date').datepicker({
            dateFormat: 'yy-mm-dd',
            maxDate: new Date(),
            changeMonth: true,
            changeYear: true
        });
    }
    
    // Handle filter changes for top attendees widget
    $('#yaat-filter-select').on('change', function() {
        var filterType = $(this).val();
        
        // If quarter is selected, show the quarter select
        if (filterType === 'quarter') {
            $('#yaat-quarter-select').parent().show();
        } else {
            $('#yaat-quarter-select').parent().hide();
        }
    });
    
    // Trigger change on page load to set initial state
    $('#yaat-filter-select').trigger('change');
});
