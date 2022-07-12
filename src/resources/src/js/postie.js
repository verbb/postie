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

Craft.Postie.ProviderConnection = Garnish.Base.extend({
    init(providerHandle) {
        this.providerHandle = providerHandle;

        this.$container = $('#settings-providers-' + this.providerHandle + '-js-postie-connection');
        this.$status = this.$container.find('.js-status')
        this.$statusText = this.$container.find('.js-status-text')
        this.$refreshBtn = this.$container.find('.js-btn-refresh')

        this.addListener(this.$refreshBtn, 'click', 'onRefresh');
    },

    onRefresh(e) {
        e.preventDefault();

        var providerSettings = {};

        this.$form = $('#tab-' + this.providerHandle);

        if (this.$form) {
            var postData = Garnish.getPostData(this.$form);
            var params = Craft.expandPostArray(postData);

            if (params && params.settings && params.settings.providers && params.settings.providers[this.providerHandle]) {
                providerSettings = params.settings.providers[this.providerHandle];
            }
        }

        var data = {
            providerHandle: this.providerHandle,
            settings: providerSettings,
        };

        this.$refreshBtn.addClass('vui-loading vui-loading-tiny');
        this.$statusText.html(Craft.t('postie', 'Connecting...'));
        this.$status.removeClass('on off');

        var cookieName = 'postie-' + this.providerHandle + '-connect';

        // Always delete the cookie
        document.cookie = cookieName + '=;';

        Craft.sendActionRequest('POST', 'postie/providers/check-connection', { data })
            .then((response) => {
                this.$statusText.html(Craft.t('postie', 'Connected'));
                this.$status.addClass('on');

                // Save as a cookie for later
                document.cookie = cookieName + '=true;';
            })
            .catch(({response}) => {
                var errorMessage = Craft.t('postie', 'An error occurred.') + '<br><br><code>' + response.data.message + '</code>';

                this.$statusText.html(Craft.t('postie', 'Error'));
                this.$status.addClass('off');

                new Craft.Postie.ProviderConnectionModal(errorMessage);
            })
            .finally(() => {
                this.$refreshBtn.removeClass('vui-loading vui-loading-tiny');
            });        
    },
});

Craft.Postie.ProviderConnectionModal = Garnish.Modal.extend({
    init(errorMessage) {
        this.$form = $('<form class="modal vui-connection-error-modal" method="post" accept-charset="UTF-8"/>').appendTo(Garnish.$bod);
        this.$body = $('<div class="body"></div>').appendTo(this.$form);
        this.$cancelBtn = $('<div class="vui-dialog-close"></div>').appendTo(this.$body);
        this.$pane = $('<div class="vui-error-pane error"></div>').appendTo(this.$body);
        this.$content = $('<div class="vui-error-content"></div>').appendTo(this.$pane);
        this.$alert = $('<span data-icon="alert"></span>').appendTo(this.$content);
        this.$errorMsg = $('<span class="error">' + errorMessage + '</span>').appendTo(this.$content);

        this.addListener(this.$cancelBtn, 'click', 'onFadeOut');

        this.base(this.$form);
    },

    onFadeOut() {
        this.$form.remove();
        this.$shade.remove();
    },
});

