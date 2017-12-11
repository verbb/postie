$(function() {

    // -----------------------------------------
    // Quick enable/disable all shipping methods
    // -----------------------------------------

    $(document).on('change', '#allShippingMethods', function() {
        var $lightSwitchFields = $('#shippingMethods').find('.lightswitch');

        if ($(this).hasClass('on')) {
            $lightSwitchFields.addClass('on').attr('aria-checked', true);
            $lightSwitchFields.find('.lightswitch-container').animate({'margin-left': '0px'}, 100);
            $lightSwitchFields.find('input').val(1);
        } else {
            $lightSwitchFields.removeClass('on').attr('aria-checked', false);
            $lightSwitchFields.find('.lightswitch-container').animate({'margin-left': '-11px'}, 100);
            $lightSwitchFields.find('input').val(0);
        }
    });
});