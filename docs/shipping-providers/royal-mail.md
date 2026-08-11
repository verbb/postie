# Royal Mail
In order to use Royal Mail, you'll need to ensure you are using a valid United Kingdom address as your store location. You'll also need to ensure your Craft Commerce default currency is set to Pounds Sterling.

Royal Mail do not offer live rates via their API. Prices are calculated from built-in Royal Mail price tables (currently through April 2026).

## Support
Royal Mail supports the following APIs:
- Rates
- Tracking
- Labels (via [Click & Drop](https://www.royalmail.com/business/shipping/click-and-drop))

## API Credentials
In order to use Royal Mail tracking, you'll need to connect to their developer API.

1. Go to <a href="https://developer.royalmail.net/api" target="_blank">Royal Mail</a> and login to your account.
1. From the **My Apps** section, follow the prompts to create a new app.
1. Copy the **API Key** from Royal Mail and paste in the **Client ID** field in Postie.
1. Copy the **API Secret** from Royal Mail and paste in the **Client Secret** field in Postie.

## Click & Drop Labels
To create shipping labels via Click & Drop when lodging shipments from Commerce orders:

1. Set the provider **API** to **All**.
1. Enable **Use Click & Drop Labels**.
1. Log in to <a href="https://business.parcel.royalmail.com/" target="_blank">Royal Mail Click & Drop</a> and generate an API key.
1. Paste the key into the **Click & Drop API Key** field in Postie.

:::tip
Postie calculates checkout rates locally and does not sync Craft orders into the Click & Drop multi-channel dashboard. Click & Drop support here is for generating labels from Postie shipments.
:::
