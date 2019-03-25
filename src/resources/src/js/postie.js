// ==========================================================================

// Postie Plugin for Craft CMS
// Author: Verbb - https://verbb.io/

// ==========================================================================

if (typeof Craft.Postie === typeof undefined) {
    Craft.Postie = {};
}

$(function() {

    // -----------------------------------------
    // Quick enable/disable all shipping methods
    // -----------------------------------------

    $(document).on('change', '.provider-shipping-methods-all-switch .lightswitch', function() {
        var $lightSwitchFields = $('.provider-shipping-methods').find('.lightswitch');

        if ($(this).hasClass('on')) {
            $lightSwitchFields.each(function() {
                $(this).data('lightswitch').turnOn();
            })
        } else {
            $lightSwitchFields.each(function() {
                $(this).data('lightswitch').turnOff();
            })
        }
    });
});