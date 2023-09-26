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

Craft.Postie.ProviderRatesTest = Garnish.Base.extend({
    init() {
        this.$result = $('.js-postie-rates-test-results')
        this.$testBtn = $('.js-postie-rates-test-btn')

        this.addListener(this.$testBtn, 'click', 'runTest');
    },

    runTest(e) {
        e.preventDefault();

        var data = $('#main-form').serialize();

        this.$testBtn.addClass('vui-loading');
        this.$result.html('');

        Craft.sendActionRequest('POST', 'postie/providers/test-rates', { data })
            .then((response) => {
                if (response.data.errors.length) {
                    throw new Error({ response: { data: JSON.stringify(response.data.errors) } });
                }

                var $table = $('<div class="postie-rates-tester-table"></div>');
                var $ul = $('<ul></ul').appendTo($table);

                $.each(response.data.rates, function(index, item) {
                    $('<li><span class="label">' + item.serviceName + '</span> <code>' + item.serviceCode + '</code> <span class="price">$' + item.rate + '</span></li>').appendTo($ul);
                })

                this.$result.html($table);
            })
            .catch(({response}) => {
                var errorMessage = '<br><code>' + response.data.message + '</code>';

                this.$result.html('<span class="error">' + errorMessage + '</span>');
            })
            .finally(() => {
                this.$testBtn.removeClass('vui-loading');
            });        
    },
});

