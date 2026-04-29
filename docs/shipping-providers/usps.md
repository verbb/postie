# USPS

## Support
USPS supports the following APIs:
- Rates
- Tracking
- Labels

## API Credentials
In order to use USPS, you'll need to connect to their API. 

1. Go to <a href="https://developer.usps.com/apis" target="_blank">USPS</a> and login to your account.
1. From the **Apps** section, follow the prompts to create a new app.
1. Copy the **Consumer Key** from USPS and paste in the **Client ID** field in Postie.
1. Copy the **Consumer Secret** from USPS and paste in the **Client Secret** field in Postie.
1. Choose a **Rate Price Type** in Postie for rate lookups. Most setups should use **Commercial** or **Retail**. Only use **Contract / Negotiated** when USPS has enabled contract pricing for your account.
1. If you plan to create labels or use **Contract / Negotiated** rates, locate the **Business Account Access** email sent by USPS upon creating your USPS business account.
1. Copy the **EPS Account Number** from this email and paste in the **Account Number** field in Postie. This is required for labels and contract pricing, but should be omitted from standard rate lookups.

To create labels, you'll be required to supply a few more details.

1. Locate the **Business Account Access** email sent by USPS upon creating your USPS business account.
1. Copy the **CRID** from this email and paste in the **Customer Registration ID** field in Postie.
1. Copy the **MID** from this email and paste in the **Mailer ID** field in Postie.
