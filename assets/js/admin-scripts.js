
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
                        // Instead of reloading the page, update the UI
                        $('[data-attendance-id="' + currentAttendanceId + '"]').parent().empty();
                        deleteModal.hide();
                    } else {
                        alert(response.data.message);
                        deleteModal.hide();
                    }
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
                    
                    // Instead of reloading the page, update UI or load specific section
                    updateTopAttendees();
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
    
    // Function to update top attendees section without page reload
    function updateTopAttendees() {
        var filterType = $('#yaat-filter-select').val();
        var year = $('#yaat-year-select').val();
        var month = $('#yaat-month-select').val();
        var quarter = $('#yaat-quarter-select').val() || 1;
        
        $.ajax({
            url: yaat_admin.ajax_url,
            method: 'POST',
            data: {
                action: 'yaat_load_top_attendees',
                nonce: yaat_admin.nonce,
                filter_type: filterType,
                year: year,
                month: month,
                quarter: quarter,
                limit: 5
            },
            success: function(response) {
                if (response.success && response.data.attendees) {
                    var attendeesList = $('.yaat-top-attendees-list');
                    attendeesList.empty();
                    
                    if (response.data.attendees.length === 0) {
                        attendeesList.parent().html('<div class="yaat-no-data">' + 
                            'No attendance data found for this period.</div>');
                    } else {
                        $.each(response.data.attendees, function(index, attendee) {
                            attendeesList.append(
                                '<li class="yaat-top-attendee-item">' +
                                '<div>' +
                                '<span class="yaat-top-attendee-rank">' + (index + 1) + '.</span>' +
                                '<span class="yaat-top-attendee-name">' + attendee.name + '</span>' +
                                '</div>' +
                                '<span class="yaat-attendance-count">' + 
                                attendee.count + ' ' + (attendee.count === 1 ? 'day' : 'days') +
                                '</span>' +
                                '</li>'
                            );
                        });
                    }
                    
                    // Update filter display
                    $('.yaat-filter-display').text('Showing top attendees for: ' + response.data.filter_display);
                }
            }
        });
    }
    
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
        
        // Update top attendees without reloading page
        updateTopAttendees();
    });
    
    // Add event listeners for the filter form submission
    $('.yaat-filter-form select').on('change', function() {
        updateTopAttendees();
    });
    
    // Prevent default form submissions to avoid page reloads
    $('.yaat-filters form').on('submit', function(e) {
        e.preventDefault();
        updateTopAttendees();
    });
    
    // Trigger change on page load to set initial state
    $('#yaat-filter-select').trigger('change');
    
    // Handle user tracking checkbox changes
    $(document).on('change', '.yaat-track-user-checkbox', function() {
        var userId = $(this).data('user-id');
        var isTracked = $(this).prop('checked');
        
        $.ajax({
            url: yaat_admin.ajax_url,
            method: 'POST',
            data: {
                action: 'yaat_update_user_tracking',
                nonce: yaat_admin.nonce,
                user_id: userId,
                track: isTracked ? 1 : 0
            },
            success: function(response) {
                if (!response.success) {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
            }
        });
    });
});
