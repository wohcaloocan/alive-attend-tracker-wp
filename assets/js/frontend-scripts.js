
/**
 * Youth Alive Attendance Tracker Frontend Scripts
 */

jQuery(document).ready(function($) {
    
    // Mark attendance button click handler
    $('#yaat-mark-attendance').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var $messageEl = $('#yaat-message');
        
        // Disable button to prevent multiple submissions
        $button.prop('disabled', true).text('Processing...');
        
        $.ajax({
            url: yaat_front.ajax_url,
            type: 'POST',
            data: {
                action: 'yaat_mark_attendance',
                nonce: yaat_front.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    $messageEl.removeClass('error').addClass('success')
                        .text(response.data.message)
                        .fadeIn();
                    
                    // Replace button with checkmark and message
                    $button.replaceWith(
                        '<div class="yaat-attendance-marked">' +
                        '<div class="yaat-checkmark">✓</div>' +
                        '<p>' + yaat_front.already_marked + '</p>' +
                        '</div>'
                    );
                } else {
                    // Show error message
                    $messageEl.removeClass('success').addClass('error')
                        .text(response.data.message)
                        .fadeIn();
                    
                    // If not logged in, provide login link
                    if (response.data.login_url) {
                        $messageEl.append(
                            '<p><a href="' + response.data.login_url + '">' +
                            'Log in to mark attendance</a></p>'
                        );
                    }
                    
                    // Re-enable button
                    $button.prop('disabled', false).text('Mark My Attendance');
                }
            },
            error: function() {
                // Show generic error message
                $messageEl.removeClass('success').addClass('error')
                    .text(yaat_front.error_message)
                    .fadeIn();
                
                // Re-enable button
                $button.prop('disabled', false).text('Mark My Attendance');
            }
        });
    });
    
});
