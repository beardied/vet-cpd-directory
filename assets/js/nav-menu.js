/**
 * CPD Nav Menu JavaScript
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Handle CPD Category Add to Menu
        $('#submit-cpd-category').on('click', function(e) {
            e.preventDefault();
            
            var checked = $('#cpdcategorychecklist input:checked');
            if (checked.length === 0) {
                return false;
            }

            var items = [];
            checked.each(function() {
                var $this = $(this);
                var $label = $this.closest('label');
                var title = $label.find('.menu-item-title').text().trim() || $label.text().trim().replace($this.val(), '');
                
                items.push({
                    '-1': {
                        'menu-item-type': 'taxonomy',
                        'menu-item-object': 'cpd_category',
                        'menu-item-object-id': $this.val(),
                        'menu-item-title': title
                    }
                });
            });

            wpNavMenu.addItemToMenu(items, wpNavMenu.addMenuItemToBottom, function() {
                checked.prop('checked', false);
            });

            return false;
        });

        // Handle CPD Tag Add to Menu
        $('#submit-cpd-tag').on('click', function(e) {
            e.preventDefault();
            
            var checked = $('#cpdtagchecklist input:checked');
            if (checked.length === 0) {
                return false;
            }

            var items = [];
            checked.each(function() {
                var $this = $(this);
                var $label = $this.closest('label');
                var title = $label.find('.menu-item-title').text().trim() || $label.text().trim().replace($this.val(), '');
                
                items.push({
                    '-1': {
                        'menu-item-type': 'taxonomy',
                        'menu-item-object': 'cpd_tag',
                        'menu-item-object-id': $this.val(),
                        'menu-item-title': title
                    }
                });
            });

            wpNavMenu.addItemToMenu(items, wpNavMenu.addMenuItemToBottom, function() {
                checked.prop('checked', false);
            });

            return false;
        });
    });

})(jQuery);
