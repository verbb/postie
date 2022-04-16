# FAQs

### Does Postie make an API request each time the shipping page is shown on checkout? [#](#does-postie-make-an-api-request-each-time-the-shipping-page-is-shown-on-checkout "Direct link to Does Postie make an API request each time the shipping page is shown on checkout?")

Fortunately no, this would be slow and unnecessary. Postie intelligently caches API requests based on the contents of your cart, and address information. If nothing apart your cart or destination address changes, then neither do the shipping calculations.

