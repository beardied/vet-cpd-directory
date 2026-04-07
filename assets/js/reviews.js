/**
 * CPD Reviews JavaScript
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Handle review form submission
        $('.cpd-review-form').on('submit', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $message = $form.siblings('.cpd-review-message');
            var $submitBtn = $form.find('.cpd-submit-review-btn');
            
            // Disable submit button
            $submitBtn.prop('disabled', true).text('Submitting...');
            
            // Gather form data
            var formData = {
                action: 'cpd_submit_review',
                nonce: cpd_reviews.nonce,
                cpd_event_id: $form.find('input[name="cpd_event_id"]').val(),
                cpd_website: $form.find('input[name="cpd_website"]').val(),
                reviewer_name: $form.find('input[name="reviewer_name"]').val(),
                reviewer_email: $form.find('input[name="reviewer_email"]').val(),
                star_rating: $form.find('input[name="star_rating"]:checked').val(),
                review_comment: $form.find('textarea[name="review_comment"]').val()
            };
            
            // Send AJAX request
            $.ajax({
                url: cpd_reviews.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        $message
                            .removeClass('error')
                            .addClass('success')
                            .html(response.data.message)
                            .show();
                        
                        // Reset form
                        $form[0].reset();
                        
                        // Hide form after success
                        setTimeout(function() {
                            $form.hide();
                        }, 3000);
                    } else {
                        $message
                            .removeClass('success')
                            .addClass('error')
                            .html(response.data.message)
                            .show();
                    }
                },
                error: function() {
                    $message
                        .removeClass('success')
                        .addClass('error')
                        .html('An error occurred. Please try again.')
                        .show();
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).text('Submit Review');
                }
            });
        });
    });

})(jQuery);
