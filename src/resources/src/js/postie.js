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

        // Don't use `#main-form` as it won't exist for a read-only page
        var data = $('#main-content').find('select, textarea, input').serialize();

        this.$testBtn.addClass('vui-loading');
        this.$result.html('');

        Craft.sendActionRequest('POST', 'postie/providers/test-rates', { data })
            .then((response) => {
                if (response.data.errors.length) {
                    throw {
                        response: {
                            data: {
                                message: JSON.stringify(response.data.errors),
                            },
                        },
                    };
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

Craft.Postie.OrderShipments = Garnish.Base.extend({
    init(settings) {
        this.orderId = settings.orderId;
        this.rateId = settings.rateId;

        this.$newBtn = $('.js-postie-new-shipment');

        this.addListener(this.$newBtn, 'click', 'openNewModal');
    },

    openNewModal(e) {
        new Craft.Postie.OrderShipmentModal({
            orderId: this.orderId,
            rateId: this.rateId,
        });
    },
});


Craft.Postie.OrderShipmentModal = Garnish.Modal.extend({
    init(settings) {
        this.orderId = settings.orderId;
        this.rateId = settings.rateId;

        this.$form = $('<form class="modal fitted create-shipment-modal" method="post" accept-charset="UTF-8"/>').appendTo(Garnish.$bod);
        this.$body = $('<div class="body"></div>').appendTo(this.$form);
        this.$spinner = $('<div class="spinner spinner-absolute"/>').appendTo(this.$body);
        this.$footer = $('<div class="footer hidden"/>').appendTo(this.$form);
        this.$footerSpinner = $('<div class="spinner right hidden"/>').appendTo(this.$footer);
        this.$buttons = $('<div class="buttons right"/>').appendTo(this.$footer);
        this.$cancelBtn = $('<input type="button" class="btn" value="' + Craft.t('postie', 'Cancel') + '" />').appendTo(this.$buttons);
        this.$createButton = $('<input type="submit" class="btn submit" value="' + Craft.t('postie', 'Create Shipment') + '" />').appendTo(this.$buttons);

        this.addListener(this.$cancelBtn, 'click', 'hide');
        this.addListener(this.$createButton, 'click', 'onSubmit');

        this.base(this.$form, settings);
    },

    onFadeIn: function() {
        var data = {
            orderId: this.orderId,
            rateId: this.rateId,
        };

        Craft.sendActionRequest('POST', 'postie/shipments/shipment-modal', { data })
            .then((response) => {
                this.$body.html(response.data.html);
                this.$footer.removeClass('hidden');

                setTimeout(() => {
                    this.updateSizeAndPosition();
                }, 100);
            })
            .catch(({response}) => {
                if (response && response.data && response.data.message) {
                    Craft.cp.displayError(response.data.message);
                } else {
                    Craft.cp.displayError();
                }
            })
            .finally(() => {
                this.$spinner.addClass('hidden');
            });

        this.base();
    },

    onSubmit: function(e) {
        e.preventDefault();

        this.$footerSpinner.removeClass('hidden');

        const data = this.$form.serialize();

        Craft.sendActionRequest('POST', 'postie/shipments/create-shipment', { data })
            .then((response) => {
                if (response.success) {
                    Craft.cp.displayNotice(Craft.t('postie', 'Shipment Created.'));

                    location.reload();
                } else {
                    Craft.cp.displayError();
                }
            })
            .catch(({response}) => {
                if (response && response.data && response.data.message) {
                    Craft.cp.displayError(response.data.message);
                } else {
                    Craft.cp.displayError();
                }
            })
            .finally(() => {
                this.$footerSpinner.addClass('hidden');
            });
    },
});