/**
 * Avatar Customizer JavaScript
 * Handles real-time avatar preview and customization
 */

jQuery(document).ready(function($) {
    'use strict';

    const AvatarCustomizer = {
        init: function() {
            this.bindEvents();
            this.updateColorValues();
        },

        bindEvents: function() {
            // Color picker changes
            $('.hs-color-picker').on('change input', this.handleColorChange.bind(this));

            // Gender change
            $('#gender').on('change', this.handleGenderChange.bind(this));

            // Item selection
            $('input[type="radio"][name^="item_"]').on('change', this.handleItemChange.bind(this));

            // Form submission
            $('#hs-avatar-customization-form').on('submit', this.handleSubmit.bind(this));
        },

        updateColorValues: function() {
            $('.hs-color-picker').each(function() {
                const $input = $(this);
                const $valueSpan = $input.siblings('.hs-color-value');
                $valueSpan.text($input.val());
            });
        },

        handleColorChange: function(e) {
            const $input = $(e.target);
            const $valueSpan = $input.siblings('.hs-color-value');
            $valueSpan.text($input.val());

            // Update preview
            this.updatePreview();
        },

        handleGenderChange: function(e) {
            // Update preview
            this.updatePreview();
        },

        handleItemChange: function(e) {
            // Update preview
            this.updatePreview();
        },

        updatePreview: function() {
            // Get current customization values
            const bodyColor = $('#body_color').val();
            const gender = $('#gender').val();
            const shirtColor = $('#shirt_color').val();
            const pantsColor = $('#pants_color').val();

            // Get equipped items
            const equippedItems = [];
            $('input[type="radio"][name^="item_"]:checked').each(function() {
                const itemId = parseInt($(this).val());
                if (itemId > 0) {
                    equippedItems.push(itemId);
                }
            });

            // Fetch updated avatar preview
            $.ajax({
                url: hs_avatar_ajax.rest_url + 'preview',
                method: 'POST',
                headers: {
                    'X-WP-Nonce': hs_avatar_ajax.nonce
                },
                data: JSON.stringify({
                    body_color: bodyColor,
                    gender: gender,
                    shirt_color: shirtColor,
                    pants_color: pantsColor,
                    equipped_items: equippedItems
                }),
                contentType: 'application/json',
                success: function(response) {
                    // This would require a preview endpoint
                    // For now, we'll just refresh on save
                },
                error: function(xhr, status, error) {
                    console.error('Preview update failed:', error);
                }
            });
        },

        handleSubmit: function(e) {
            e.preventDefault();

            const $form = $(e.target);
            const $status = $('#hs-avatar-save-status');
            const $submitBtn = $form.find('button[type="submit"]');

            // Disable submit button
            $submitBtn.prop('disabled', true).text('Saving...');
            $status.text('').removeClass('error success');

            // Get form data
            const bodyColor = $('#body_color').val();
            const gender = $('#gender').val();
            const shirtColor = $('#shirt_color').val();
            const pantsColor = $('#pants_color').val();

            // Get equipped items
            const equippedItems = [];
            $('input[type="radio"][name^="item_"]:checked').each(function() {
                const itemId = parseInt($(this).val());
                if (itemId > 0) {
                    equippedItems.push(itemId);
                }
            });

            // Send to API
            $.ajax({
                url: hs_avatar_ajax.rest_url + 'customization',
                method: 'POST',
                headers: {
                    'X-WP-Nonce': hs_avatar_ajax.nonce
                },
                data: JSON.stringify({
                    body_color: bodyColor,
                    gender: gender,
                    shirt_color: shirtColor,
                    pants_color: pantsColor,
                    equipped_items: equippedItems
                }),
                contentType: 'application/json',
                success: function(response) {
                    $status.text('Avatar saved successfully! Refreshing...').addClass('success');

                    // Refresh preview
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                },
                error: function(xhr, status, error) {
                    let errorMessage = 'Failed to save avatar.';

                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }

                    $status.text(errorMessage).addClass('error');
                    $submitBtn.prop('disabled', false).text('Save Avatar');
                },
                complete: function() {
                    // Re-enable submit button after a delay
                    setTimeout(function() {
                        $submitBtn.prop('disabled', false).text('Save Avatar');
                    }, 2000);
                }
            });
        }
    };

    // Initialize
    AvatarCustomizer.init();
});
